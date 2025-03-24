<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\ApiResponseService;
use App\Models\BusinessProfile;
use App\Models\Subscription;
use App\Http\Middleware\CheckBusinessAuthorization; // Import the middleware
use Stripe\Stripe;
use Stripe\PaymentIntent;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        // Base validation for all users
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer|exists:roles,id',
            'profile_img' => 'nullable|string',
        ]);

        // Validate business fields before creating any user
        if ($request->role_id === 3) {
            $request->validate([
                'business_name' => 'required|string',
                'category_id' => 'required|integer|exists:categories,id',
                'district' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'opening_hour' => 'nullable|string',
                'closing_hour' => 'nullable|string',
                'main_img' => 'nullable|string',
                'subscription_type' => 'required|string|in:monthly,yearly',
                'payment_method' => 'required|string',
            ]);
        }

        // Create user after all validations pass
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => $request->role_id,
            'profile_img' => $request->profile_img ?? null,
        ]);

        // Create business profile and subscription if role is business
        if ($user->role_id === 3) {
            BusinessProfile::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'category_id' => $request->category_id,
                'district' => $request->district,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'opening_hour' => $request->opening_hour,
                'closing_hour' => $request->closing_hour,
                'main_img' => $request->main_img,
            ]);

            $startDate = now();
            $endDate = $request->subscription_type === 'monthly' ? $startDate->copy()->addMonth() : $startDate->copy()->addYear();

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

                Subscription::create([
                    'business_user_id' => $user->id,
                    'type' => $request->subscription_type,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'active' => true,
                    'price' => $price,
                    'payment_status' => 'paid',
                ]);
            } catch (\Exception $e) {
                return ApiResponseService::error($e->getMessage(), null, 500);
            }
        }

        $token = JWTAuth::fromUser($user);
        $user->role_name = $user->role->name;
        unset($user->updated_at, $user->created_at);

        return ApiResponseService::success('User registered successfully', compact('user', 'token'), 201);
    }

    /**
     * Login and return a JWT token.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return ApiResponseService::error('Unauthorized', null, 401);
        }

        $user = Auth::user();

        $user->role_name = $user->role->name;

        unset($user->updated_at, $user->created_at);

        if ($user->role_id === 3) {
            // Apply the CheckBusinessAuthorization middleware logic
            $middleware = new CheckBusinessAuthorization();
            $response = $middleware->handle($request, function () {
                return null;
            });

            // If the middleware returns a response return it
            if ($response) {
                return $response;
            }

            // Add business profile and subscription details
            $businessProfile = $user->businessProfile;
            unset($businessProfile->user);
            $user->business_profile = $businessProfile;

            $subscription = $user->subscription;
            $user->subscription = $subscription;
        }

        return ApiResponseService::success('Login successful', compact('user', 'token'));
    }

    /**
     * Get the authenticated user.
     */
    public function me()
    {
        $user = Auth::user();
        $user->role_name = $user->role->name;
        unset($user->updated_at, $user->created_at);

        if ($user->role_id === 3) {
            $businessProfile = $user->businessProfile;
            unset($businessProfile->user);
            $user->business_profile = $businessProfile;

            $subscription = $user->subscription;
            $user->subscription = $subscription;
        }

        return ApiResponseService::success('Authenticated user retrieved successfully', ['user' => $user]);
    }

    /**
     * Logout the user (invalidate the token).
     */
    public function logout()
    {
        Auth::logout();
        return ApiResponseService::success('Successfully logged out');
    }

    /**
     * Refresh a token.
     */
    public function refresh()
    {
        return ApiResponseService::success('Token refreshed successfully', [
            'token' => Auth::refresh(),
        ]);
    }
}
