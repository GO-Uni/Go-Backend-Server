<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\UserActivity;
use App\Services\OpenAIService;
use App\Services\ApiResponseService;
use App\Models\Category;

class UserActivityController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
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

                return $destination;
            });

        return ApiResponseService::success('Recommended destinations retrieved successfully.', $destinations);
    }

    public function chatbotResponse($userId)
    {
        // Retrieve the userMessage from the request body
        $userMessage = request()->input('userMessage');

        if (!$userMessage) {
            return ApiResponseService::error("The 'userMessage' field is required.", null, 400);
        }

        // Use OpenAI to extract the destination name, category, or district from the user message
        $aiResponse = $this->openAIService->nameDetector(
            "Extract the destination name, category, or district from this sentence: \"$userMessage\". If no destination, category, or district is found, respond with 'None'."
        );

        if (empty($aiResponse)) {
            return ApiResponseService::error("Unable to process your request at the moment.", null, 500);
        }

        $extractedName = trim($aiResponse);

        if (strtolower($extractedName) === 'none') {
            return ApiResponseService::success(
                "No destinations, categories, or districts were found based on your input. Here are some recommendations based on your recent activities:",
                $this->fallbackToRecommendations($userId, $userMessage)->getData()->data
            );
        }

        // Check if the extracted name matches a district
        $districtBusinesses = BusinessProfile::where('district', 'LIKE', '%' . $extractedName . '%')->get();

        if ($districtBusinesses->isNotEmpty()) {
            return ApiResponseService::success(
                "Here are some businesses in the district '{$extractedName}':",
                $districtBusinesses
            );
        }

        // Match the extracted name with a category using AI
        $matchedCategory = $this->matchCategory($extractedName);

        if ($matchedCategory) {
            // Fetch destinations in the matched category
            $categoryDestinations = BusinessProfile::where('category_id', $matchedCategory->id)->get();

            if ($categoryDestinations->isNotEmpty()) {
                return ApiResponseService::success(
                    "Here are some destinations in the category '{$matchedCategory->name}':",
                    $categoryDestinations
                );
            }
        }

        // Check if the extracted name matches a specific destination
        $specificDestination = BusinessProfile::where('business_name', 'LIKE', '%' . $extractedName . '%')->first();

        if ($specificDestination) {
            return ApiResponseService::success(
                "Here is some information about {$specificDestination->business_name}:",
                $specificDestination
            );
        }

        // Fallback to recommendations if no match is found
        return ApiResponseService::success(
            "No exact matches were found. Based on your recent activities, here are some recommended places:",
            $this->fallbackToRecommendations($userId, $userMessage)->getData()->data
        );
    }

    /**
     * Match the extracted name with a category from the database using AI.
     */
    private function matchCategory($extractedName)
    {
        // Fetch all categories from the database
        $categories = Category::pluck('name')->toArray();

        // Convert category names to a comma-separated string for AI
        $categoriesList = implode(', ', $categories);

        // capitalize first letter
        $normalizedInput = ucfirst(strtolower(trim($extractedName)));

        // Use OpenAI to match the input with the closest category
        $aiResponse = $this->openAIService->nameDetector(
            "From the following list of categories: [$categoriesList], determine which category best matches the input: \"$normalizedInput\". If no match is found, respond with 'None'."
        );

        $matchedCategoryName = ucfirst(strtolower(trim($aiResponse)));

        if (strtolower($matchedCategoryName) === 'none') {
            return null;
        }

        // Find and return the matched category from the database
        return Category::whereRaw('LOWER(name) = ?', [strtolower($matchedCategoryName)])->first();
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
