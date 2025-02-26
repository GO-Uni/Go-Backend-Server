<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ApiResponseService;
use App\Models\SavedDestination;
use App\Models\UserActivity;
use App\Models\ActivityType;
use App\Models\BusinessProfile;

class ActivityController extends Controller
{
    /**
     * Save a destination.
     */
    public function saveDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
        ]);

        // Check if the destination is already saved
        $existingSavedDestination = SavedDestination::where('user_id', $user->id)
            ->where('business_user_id', $request->business_user_id)
            ->first();

        if ($existingSavedDestination) {
            return ApiResponseService::error('Destination already saved.', null, 400);
        }

        // Getting the category name of destination
        $businessProfile = BusinessProfile::where('user_id', $request->business_user_id)->first();
        if (!$businessProfile) {
            return ApiResponseService::error('Business profile not found.', null, 404);
        }
        $categoryName = $businessProfile->category->name;

        $savedDestination = SavedDestination::create([
            'user_id' => $user->id,
            'business_user_id' => $request->business_user_id,
        ]);

        $activityType = ActivityType::where('name', 'save')->first();
        UserActivity::create([
            'user_id' => $user->id,
            'business_user_id' => $request->business_user_id,
            'activity_type_id' => $activityType->id,
            'activity_value' => null,
            'category' => $categoryName,
        ]);

        return ApiResponseService::success('Destination saved successfully', ['saved_destination' => $savedDestination]);
    }

    /**
     * Unsave a destination.
     */
    public function unsaveDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
        ]);

        // Find the saved destination record
        $savedDestination = SavedDestination::where('user_id', $user->id)
            ->where('business_user_id', $request->business_user_id)
            ->first();

        if (!$savedDestination) {
            return ApiResponseService::error('Saved destination not found.', null, 404);
        }

        // Delete the saved destination record
        $savedDestination->delete();

        $activityType = ActivityType::where('name', 'save')->first();
        UserActivity::where('user_id', $user->id)
            ->where('business_user_id', $request->business_user_id)
            ->where('activity_type_id', $activityType->id)
            ->delete();

        return ApiResponseService::success('Destination unsaved successfully');
    }

    /**
     * Rate a destination.
     */
    public function rateDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        $activityType = ActivityType::where('name', 'rate')->first();
        $userActivity = UserActivity::create([
            'user_id' => $user->id,
            'business_user_id' => $request->business_user_id,
            'activity_type_id' => $activityType->id,
            'activity_value' => (string) $request->rating,
        ]);

        return ApiResponseService::success('Destination rated successfully', ['user_activity' => $userActivity]);
    }

    /**
     * Review a destination.
     */
    public function reviewDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
            'review' => 'required|string',
        ]);

        $activityType = ActivityType::where('name', 'review')->first();
        $userActivity = UserActivity::create([
            'user_id' => $user->id,
            'business_user_id' => $request->business_user_id,
            'activity_type_id' => $activityType->id,
            'activity_value' => $request->review,
        ]);

        return ApiResponseService::success('Destination reviewed successfully', ['user_activity' => $userActivity]);
    }
}
