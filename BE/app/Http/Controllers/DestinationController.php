<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\UserActivity;
use App\Services\ApiResponseService;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DestinationController extends Controller
{
    private function getImageUrl(?string $path): ?string
    {
        return $path && trim($path) !== '' ? Storage::disk('s3')->url($path) : null;
    }
    /**
     * Get all destinations with active users
     */
    public function index()
    {
        $destinations = BusinessProfile::whereHas('user', function ($query) {
            $query->where('status', 'active');
        })->get();

        if ($destinations->isEmpty()) {
            return ApiResponseService::error('No destinations found.', null, 200);
        }

        foreach ($destinations as $destination) {
            $destination->main_img = $this->getImageUrl($destination->main_img);
        
            $reviews = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 3)
                ->with('user')
                ->get()
                ->map(function ($review) {
                    return [
                        'review_value' => $review->activity_value,
                        'user_name' => $review->user->name ?? 'Unknown',
                    ];
                });
        
            $destination->rating = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 2)->pluck('activity_value')->map(fn($v) => (float)$v)->avg() ?? 0;
        
            $destination->bookings = Booking::where('business_user_id', $destination->user_id)->get()->map(fn($b) => [
                'user_name' => $b->user->name,
                'booking_time' => $b->booking_time,
                'booking_date' => $b->booking_date,
            ]);
        }
        
        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }

    /**
     * Get all destinations grouped by user status
     */
    public function getGroupedByStatus()
    {
        // Fetch all users excluding admins (role_id = 1)
        $users = User::where('role_id', '!=', 1)->get();

        $normalUsersActive = [];
        $businessUsersActive = [];
        $bannedUsers = [];

        foreach ($users as $user) {
            if ($user->status === 'active') {
                if ($user->role_id === 2) { // Normal user
                    $normalUsersActive[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                } elseif ($user->role_id === 3) { // Business user
                    $businessProfiles = BusinessProfile::where('user_id', $user->id)->get();
                    foreach ($businessProfiles as $profile) {
                        // Fetch subscription details dynamically
                        $subscriptionDetails = $profile->subscription_details;

                        $businessUsersActive[] = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'business_name' => $profile->business_name,
                            'category' => $profile->category->name ?? 'Unknown',
                            'district' => $profile->district,
                            'hours' => date('H:i', strtotime($profile->opening_hour)) . '-' . date('H:i', strtotime($profile->closing_hour)),
                            'subscription' => $subscriptionDetails['type'] ?? 'None',
                        ];
                    }
                }
            } elseif ($user->status === 'banned') {
                $bannedUsers[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role_id === 2 ? 'Normal User' : 'Business User',
                ];
            }
        }

        $result = [
            'normal_users_active' => [
                'count' => count($normalUsersActive),
                'users' => $normalUsersActive,
            ],
            'business_users_active' => [
                'count' => count($businessUsersActive),
                'users' => $businessUsersActive,
            ],
            'banned_users' => [
                'count' => count($bannedUsers),
                'users' => $bannedUsers,
            ],
        ];

        return ApiResponseService::success('Users grouped by status retrieved successfully', $result);
    }

    private function enrichDestination($dest)
    {
        $dest->main_img = $this->getImageUrl($dest->main_img);
        $dest->reviews = UserActivity::where('business_user_id', $dest->user_id)
            ->where('activity_type_id', 3)->with('user')->get()->map(fn($r) => [
                'review_value' => $r->activity_value,
                'user_name' => $r->user->name ?? 'Unknown',
            ]);

        $dest->rating = UserActivity::where('business_user_id', $dest->user_id)
            ->where('activity_type_id', 2)->pluck('activity_value')->map(fn($v) => (float)$v)->avg() ?? 0;

        $dest->bookings = Booking::where('business_user_id', $dest->user_id)->get()->map(fn($b) => [
            'user_name' => $b->user->name,
            'booking_time' => $b->booking_time,
            'booking_date' => $b->booking_date,
        ]);
    }

    public function getByName($name)
    {
        $destinations = BusinessProfile::where('business_name', 'LIKE', "%$name%")
            ->whereHas('user', fn($q) => $q->where('status', 'active'))->get();

        if ($destinations->isEmpty()) return ApiResponseService::error('No destination found.', null, 200);
        foreach ($destinations as $d) $this->enrichDestination($d);

        return ApiResponseService::success('Destination retrieved successfully', $destinations);
    }

    /**
     * Get a destination by its ID
     */
    public function getByUserId($userId)
    {
        $destinations = BusinessProfile::where('user_id', $userId)
            ->whereHas('user', fn($q) => $q->where('status', 'active'))->get();

        if ($destinations->isEmpty()) return ApiResponseService::error('No destination found.', null, 200);
        foreach ($destinations as $d) $this->enrichDestination($d);

        return ApiResponseService::success('Destination retrieved successfully', $destinations);
    }

    /**
     * Get destinations by category (ID or name)
     */
    public function getByCategory($category)
    {
        if (!is_numeric($category)) {
            $categoryModel = Category::where('name', 'LIKE', "%$category%")->first();
            if (!$categoryModel) return ApiResponseService::error('Category not found', null, 404);
            $category = $categoryModel->id;
        }

        $destinations = BusinessProfile::where('category_id', $category)
            ->whereHas('user', fn($q) => $q->where('status', 'active'))->get();

        if ($destinations->isEmpty()) return ApiResponseService::error('No destinations found.', null, 200);
        foreach ($destinations as $d) $this->enrichDestination($d);

        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }

    /**
     * Get destinations by district
     */
    public function getByDistrict($district)
    {
        $destinations = BusinessProfile::where('district', 'ILIKE', "%$district%")
            ->whereHas('user', fn($q) => $q->where('status', 'active'))->get();

        if ($destinations->isEmpty()) return ApiResponseService::error('No destinations found.', null, 200);
        foreach ($destinations as $d) $this->enrichDestination($d);

        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }

    /**
     * Get all bookings of a business user by their business_user_id
     */
    public function getBookingsBusiness($businessUserId)
    {
        // Fetch all bookings for the given business_user_id
        $bookings = Booking::where('business_user_id', $businessUserId)->get();
        if ($bookings->isEmpty()) return ApiResponseService::error('No bookings found for this business user.', null, 404);

        return ApiResponseService::success('Bookings retrieved successfully', $bookings);
    }

    /**
     * Get all reviews of a business user by their business_user_id
     */
    public function getReviews($businessUserId)
    {
        // Fetch all reviews with the user name
        $reviews = UserActivity::where('business_user_id', $businessUserId)
            ->where('activity_type_id', 3)
            ->with('user')->get()
            ->map(fn($r) => [
                'review_value' => $r->activity_value,
                'user_name' => $r->user->name ?? 'Unknown',
            ]);

        if ($reviews->isEmpty()) return ApiResponseService::error('No reviews found for this business user.', null, 204);

        return ApiResponseService::success('Reviews retrieved successfully', $reviews);
    }

    /**
     * Get the average rating of a business user by their business_user_id
     */
    public function getRating($businessUserId)
    {
        // Fetch all ratings for the given business_user_id and calculate the average
        $rating = UserActivity::where('business_user_id', $businessUserId)
            ->where('activity_type_id', 2)
            ->pluck('activity_value')
            ->map(fn($v) => (float)$v)
            ->avg() ?? 0;

        return ApiResponseService::success('Rating retrieved successfully', ['rating' => $rating]);
    }
}
