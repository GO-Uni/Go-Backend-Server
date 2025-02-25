<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ApiResponseService;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Subscription;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Validate common fields
        $request->validate([
            'name' => 'required|string',
            'profile_img' => 'nullable|string',
        ]);

        // Validate business fields if the user is a business
        if ($user->role_id === 3) {
            $request->validate([
                'business_name' => 'required|string',
                'category_id' => 'required|integer|exists:categories,id',
                'district' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'opening_hour' => 'nullable|string',
                'closing_hour' => 'nullable|string',
                'main_img' => 'nullable|string',
                'description' => 'nullable|string'
            ]);
        }

        // Find the user from the database
        $user = User::find($user->id);

        // Update user attributes
        $user->update([
            'name' => $request->name,
            'profile_img' => $request->profile_img ?? $user->profile_img,
        ]);

        // Update business profile if the user is a business
        if ($user->role_id === 3) {
            $businessProfile = BusinessProfile::where('user_id', $user->id)->first();
            if ($businessProfile) {
                $businessProfile->update([
                    'business_name' => $request->business_name,
                    'category_id' => $request->category_id,
                    'district' => $request->district,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'opening_hour' => $request->opening_hour,
                    'closing_hour' => $request->closing_hour,
                    'main_img' => $request->main_img,
                    'description' => $request->description,
                ]);
            }
        }

        return ApiResponseService::success('Profile updated successfully', ['user' => $user]);
    }

    public function updateSubscription(Request $request)
    {
        $user = Auth::user();

        // Validate subscription field
        $request->validate([
            'subscription_type' => 'required|string|in:monthly,yearly',
        ]);

        $subscription = Subscription::where('business_user_id', $user->id)->where('active', true)->first();

        if ($subscription) {
            // Check if the subscription type has changed
            if ($subscription->type !== $request->subscription_type) {
                // Calculate the new end date based on the new subscription type
                $newEndDate = $request->subscription_type === 'monthly' ? $subscription->end_date->copy()->addMonth() : $subscription->end_date->copy()->addYear();

                $subscription->update([
                    'type' => $request->subscription_type,
                    'end_date' => $newEndDate,
                ]);
            }
        }

        return ApiResponseService::success('Subscription updated successfully', ['subscription' => $subscription]);
    }
}
