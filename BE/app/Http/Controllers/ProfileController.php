<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ApiResponseService;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Subscription;
use Stripe\Stripe;
use Stripe\PaymentIntent;

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
                'counter_booking' => 'nullable|integer',
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
                if ($request->filled('counter_booking')) {
                    $businessProfileData['counter_booking'] = $request->counter_booking;
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
            'payment_method' => 'required|string',
        ]);

        $subscription = Subscription::where('business_user_id', $user->id)->where('active', true)->first();

        if (!$subscription) {
            return ApiResponseService::error('No active subscription found.', null, 404);
        }

        // Set the subscription price based on the type
        $price = $request->subscription_type === 'monthly' ? 1499 : 14999; // Amount in cents

        // Process the payment
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $price,
                'currency' => 'usd',
                'payment_method' => $request->payment_method,
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
            ]);

            // Check if the subscription type has changed
            if ($subscription->type !== $request->subscription_type) {
                // Calculate the new end date based on the new subscription type
                $newEndDate = $request->subscription_type === 'monthly' ? $subscription->end_date->copy()->addMonth() : $subscription->end_date->copy()->addYear();

                // Update the subscription
                $subscription->update([
                    'type' => $request->subscription_type,
                    'end_date' => $newEndDate,
                    'price' => $price,
                    'payment_status' => 'paid',
                    'active' => true,
                ]);
            }

            return ApiResponseService::success('Subscription updated successfully', [
                'subscription' => [
                    'id' => $subscription->id,
                    'business_user_id' => $subscription->business_user_id,
                    'type' => $subscription->type,
                    'start_date' => $subscription->start_date,
                    'end_date' => $subscription->end_date,
                    'active' => $subscription->active,
                    'price' => $subscription->price,
                ],
                'payment_intent' => [
                    'id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status,
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponseService::error($e->getMessage(), null, 500);
        }
    }
}
