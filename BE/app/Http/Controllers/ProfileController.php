<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ApiResponseService;
use App\Services\ImageService;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Subscription;
use Stripe\Stripe;
use Stripe\PaymentIntent;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function updateProfile(Request $request)
    {
        $user = User::find(Auth::id());

        // Validate common fields
        $request->validate([
            'name' => 'nullable|string',
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

    public function uploadProfileImage(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'profile_img' => 'required|image|max:2048',
        ]);

        // Upload to S3 and get the relative path
        $path = $this->imageService->uploadImage($request->file('profile_img'), $user);

        // Delete old user profile image if exists
        if ($user->profile_img) {
            $this->imageService->deleteImage([$user->profile_img]);
        }

        // Save to users.profile_img
        $user->update(['profile_img' => $path]);

        $response = [
            'profile_img' => Storage::disk('s3')->url($path),
        ];

        // If business user, also update business_profiles.main_img
        if ($user->role_id === 3) {
            $businessProfile = BusinessProfile::where('user_id', $user->id)->first();

            if ($businessProfile) {
                // Delete old business main_img if exists
                if ($businessProfile->main_img) {
                    $this->imageService->deleteImage([$businessProfile->main_img]);
                }

                $businessProfile->update(['main_img' => $path]);
                $response['main_img'] = Storage::disk('s3')->url($path);
            }
        }

        return ApiResponseService::success('Profile image updated successfully', $response);
    }

    public function uploadBusinessMainImage(Request $request)
    {
        $user = Auth::user();

        // Ensure the user is a business
        if ($user->role_id !== 3) {
            return ApiResponseService::error('Only business users can upload main images.', null, 403);
        }

        // Validate the uploaded file
        $request->validate([
            'main_img' => 'required|image|max:2048',
        ]);

        $uploadedImages = $this->imageService->uploadImages($request->file('main_img'), $user);

        $businessProfile = BusinessProfile::where('user_id', $user->id)->first();
        if ($businessProfile && !empty($uploadedImages)) {

            if ($businessProfile->main_img) {
                $this->imageService->deleteImage([$businessProfile->main_img]);
            }
    
            $path = $uploadedImages[0]->path_name;
    
            $businessProfile->update(['main_img' => $path]);
    

            $url = Storage::disk('s3')->url($path);
    
            return ApiResponseService::success('Business main image updated successfully', [
                'main_img' => $url,
            ]);
        }
    
        return ApiResponseService::success('Business main image updated successfully', [
            'main_img' => $uploadedImages[0]->path_name,
            'main_img_url' => Storage::disk('s3')->url($uploadedImages[0]->path_name),
        ]);
    }
}
