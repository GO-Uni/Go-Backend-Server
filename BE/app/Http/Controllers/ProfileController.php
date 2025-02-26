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
            'name' => 'nullable|string',
            'profile_img' => 'nullable|string',
        ]);

        // Validate business fields if the user is a business
        if ($user->role_id === 3) {
            $request->validate([
                'business_name' => 'nullable|string',
                'category_id' => 'nullable|integer|exists:categories,id',
                'district' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'opening_hour' => 'nullable|string',
                'closing_hour' => 'nullable|string',
                'main_img' => 'nullable|string',
                'description' => 'nullable|string',
            ]);
        }

        // Find the user from the database
        $user = User::find($user->id);

        // Update user attributes
        $userData = [];
        if ($request->filled('name')) {
            $userData['name'] = $request->name;
        }
        if ($request->filled('profile_img')) {
            $userData['profile_img'] = $request->profile_img;
        }
        if (!empty($userData)) {
            $user->update($userData);
        }

        // Update business profile if the user is business
        if ($user->role_id === 3) {
            $businessProfile = BusinessProfile::where('user_id', $user->id)->first();
            if ($businessProfile) {
                $businessProfileData = [];
                if ($request->filled('business_name')) {
                    $businessProfileData['business_name'] = $request->business_name;
                }
                if ($request->filled('category_id')) {
                    $businessProfileData['category_id'] = $request->category_id;
                }
                if ($request->filled('district')) {
                    $businessProfileData['district'] = $request->district;
                }
                if ($request->filled('latitude')) {
                    $businessProfileData['latitude'] = $request->latitude;
                }
                if ($request->filled('longitude')) {
                    $businessProfileData['longitude'] = $request->longitude;
                }
                if ($request->filled('opening_hour')) {
                    $businessProfileData['opening_hour'] = $request->opening_hour;
                }
                if ($request->filled('closing_hour')) {
                    $businessProfileData['closing_hour'] = $request->closing_hour;
                }
                if ($request->filled('main_img')) {
                    $businessProfileData['main_img'] = $request->main_img;
                }
                if ($request->filled('description')) {
                    $businessProfileData['description'] = $request->description;
                }
                if (!empty($businessProfileData)) {
                    $businessProfile->update($businessProfileData);
                }
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
