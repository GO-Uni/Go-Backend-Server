<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\UserActivity;
use App\Services\OpenAIService;
use App\Services\ApiResponseService;

class UserActivityController extends Controller
{
    /**
     * Get all activities of a user
     */
    public function getUserActivities($userId)
    {
        // Fetch all user activities for the given user ID
        $activities = UserActivity::where('user_id', $userId)->get();

        if ($activities->isEmpty()) {
            return ApiResponseService::error('No activities found for this user.', null, 404);
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

        // Check if activities exist
        if ($activitiesResponse->getStatusCode() !== 200) {
            return $activitiesResponse;
        }

        // Extract activities data
        $activities = $activitiesResponse->getData()->data;

        // Get recommendations from OpenAI
        $recommendations = $openAIService->getRecommendations($activities);

        // Extract recommended categories from the OpenAI response
        $categories = explode("\n", trim($recommendations)); // Split by newline and trim whitespace

        // Fetch random destinations from recommended categories
        $destinations = BusinessProfile::whereHas('category', function ($query) use ($categories) {
            $query->whereIn('name', $categories);
        })
            ->inRandomOrder()
            ->get();

        return ApiResponseService::success('Recommended destinations retrieved successfully.', $destinations);
    }
}
