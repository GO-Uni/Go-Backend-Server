<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\UserActivity;
use App\Services\OpenAIService;
use App\Services\ApiResponseService;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class UserActivityController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    private function getImageUrl(?string $path): ?string
    {
        return $path ? Storage::disk('s3')->url($path) : null;
    }

    /**
     * Get all activities of a user
     */
    public function getUserActivities($userId)
    {
        // Fetch all user activities for the given user ID
        $activities = UserActivity::where('user_id', $userId)->get();

        if ($activities->isEmpty()) {
            return ApiResponseService::error('No activities found for this user.', null, 200);
        }

        return ApiResponseService::success('User activities retrieved successfully.', $activities);
    }

    /**
     * Recommend destinations based on user activities
     */
    public function recommendDestinations($userId, OpenAIService $openAIService)
    {
        // Fetch user activities
        $activitiesResponse = $this->getUserActivities($userId);

        // return response()->json([
        //     "data"=> $activitiesResponse->getData()
        // ]);

        // Check if activities exist
        if ($activitiesResponse->getStatusCode() !== 200) {
            return $activitiesResponse;
        }

        // Extract activities data
        $activities = $activitiesResponse->getData()->data;

        // Filter each activity to only include the required fields
        $filteredActivities = collect($activities)->map(function ($activity) {
            return [
                'activity_type_name' => $activity->activity_type_name,
                'activity_value' => $activity->activity_value,
                'category' => $activity->category
            ];
        })->all();

        // Get recommendations from OpenAI
        $recommendations = $openAIService->getRecommendations($filteredActivities);

        // Extract recommended categories from the OpenAI response
        $categories = explode("\n", trim($recommendations)); // Split by newline and trim whitespace

        // Fetch random destinations from recommended categories
        $destinations = BusinessProfile::whereHas('category', function ($query) use ($categories) {
            $query->whereIn('name', $categories);
        })
            ->inRandomOrder()
            ->get()
            ->map(function ($destination) {
                // Add the rating for each destination
                $rating = UserActivity::where('business_user_id', $destination->user_id)
                    ->where('activity_type_id', 2)
                    ->pluck('activity_value')
                    ->map(function ($value) {
                        return (float) $value;
                    })
                    ->avg() ?? 0;

                $destination->rating = $rating;
                $destination->main_img = $this->getImageUrl($destination->main_img);

                return $destination;
            });

        return ApiResponseService::success('Recommended destinations retrieved successfully.', $destinations);
    }

    public function chatbotResponse($userId)
    {
        $userMessage = request()->input('userMessage');

        if (!$userMessage) {
            return ApiResponseService::error("The 'userMessage' field is required.", null, 400);
        }

        // 1. First try direct business name match from database
        $allBusinessNames = BusinessProfile::pluck('business_name')->all();
        $extractedName = $this->findBestBusinessNameMatch($userMessage, $allBusinessNames);

        // 2. If no direct match found, use AI extraction
        if (!$extractedName) {
            $aiResponse = $this->openAIService->nameDetector(
                "From this message: \"$userMessage\", extract any: 
            1. Business/place names (even if it is unknown worldwide)
            2. Categories/types (like hotel, restaurant, museum)
            3. Districts/locations
            Return the most relevant one or 'None' if none found."
            );

            if (empty($aiResponse)) {
                return ApiResponseService::error("Unable to process your request.", null, 500);
            }

            $extractedName = trim($aiResponse);
        }

        if (strtolower($extractedName) === 'none') {
            $recommendations = $this->fallbackToRecommendations($userId, $userMessage)->getData()->data;
            $names = collect($recommendations)->pluck('business_name')->implode(', ');
            return ApiResponseService::success(
                "No specific places found. Try these recommendations: $names",
                null
            );
        }

        // 1. First try district match
        $districtBusinesses = BusinessProfile::where('district', 'LIKE', "%$extractedName%")->get();
        if ($districtBusinesses->isNotEmpty()) {
            $names = $districtBusinesses->pluck('business_name')->implode(', ');
            return ApiResponseService::success(
                "Destinations in $extractedName: $names",
                null
            );
        }

        // 2. Try category match with improved matching
        $matchedCategory = $this->matchCategory($extractedName);
        if ($matchedCategory) {
            $categoryDestinations = BusinessProfile::where('category_id', $matchedCategory->id)->get();
            if ($categoryDestinations->isNotEmpty()) {
                $names = $categoryDestinations->pluck('business_name')->implode(', ');
                return ApiResponseService::success(
                    "Places in {$matchedCategory->name} category: $names",
                    null
                );
            }
        }

        // 3. Try direct business match (again with the extracted name)
        $specificDestination = BusinessProfile::where('business_name', 'LIKE', "%$extractedName%")->first();
        if ($specificDestination) {
            $description = $specificDestination->description ?? "No Description available";
            return ApiResponseService::success(
                "Information about {$specificDestination->business_name}: $description",
                null
            );
        }

        // Final fallback
        $recommendations = $this->fallbackToRecommendations($userId, $userMessage)->getData()->data;
        $names = collect($recommendations)->pluck('business_name')->implode(', ');
        return ApiResponseService::success(
            "No exact matches. Try these: $names",
            null
        );
    }

    private function findBestBusinessNameMatch($userMessage, $businessNames)
    {
        $userMessage = strtolower($userMessage);
        $businessNames = array_map('strtolower', $businessNames);

        // First check for exact matches
        foreach ($businessNames as $index => $name) {
            if (strpos($userMessage, $name) !== false) {
                // Return the original case version
                return BusinessProfile::whereRaw('LOWER(business_name) = ?', [$name])->first()->business_name;
            }
        }

        // Then check for partial matches
        foreach ($businessNames as $index => $name) {
            similar_text($userMessage, $name, $percent);
            if ($percent > 80) { 
                return BusinessProfile::whereRaw('LOWER(business_name) = ?', [$name])->first()->business_name;
            }
        }

        return null;
    }

    private function matchCategory($extractedName)
    {
        $categories = Category::all();

        // Normalize the input 
        $normalizedInput = strtolower(trim(preg_replace('/s$/', '', $extractedName)));

        // First try direct match
        foreach ($categories as $category) {
            $normalizedCategory = strtolower(preg_replace('/s$/', '', $category->name));
            if ($normalizedCategory === $normalizedInput) {
                return $category;
            }
        }

        // If no direct match, use AI for fuzzy matching
        $categoriesList = $categories->pluck('name')->implode(', ');

        $aiResponse = $this->openAIService->nameDetector(
            "From these categories: [$categoriesList], which one best matches '$extractedName'? 
        Consider singular/plural forms and similar meanings. Return only the best match or 'None'."
        );

        $matchedName = trim($aiResponse);
        if (strtolower($matchedName) === 'none') {
            return null;
        }

        return $categories->first(function ($cat) use ($matchedName) {
            return strtolower($cat->name) === strtolower($matchedName);
        });
    }

    private function fallbackToRecommendations($userId, $userMessage)
    {
        $recommendationsResponse = $this->recommendDestinations($userId, $this->openAIService);

        if ($recommendationsResponse->getStatusCode() !== 200) {
            return ApiResponseService::error('Unable to fetch recommendations at the moment.', null, 500);
        }

        $destinations = $recommendationsResponse->getData()->data;

        $response = "Based on your recent activities, the recommended places are: ";
        $response .= $this->openAIService->generateChatbotResponse($userMessage, $destinations);

        return ApiResponseService::success($response, $destinations);
    }
}
