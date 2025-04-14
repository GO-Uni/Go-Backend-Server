<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\UserActivity;
use App\Services\ApiResponseService;
use App\Models\Booking;
use App\Models\User;

class DestinationController extends Controller
{
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
            // List all review values
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

            // Calculate the average rating
            $rating = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0; // Set to 0 if no ratings

            $destination->reviews = $reviews;
            $destination->rating = $rating;

            // Fetch bookings
            $bookings = Booking::where('business_user_id', $destination->user_id)
                ->get()
                ->map(function ($booking) {
                    return [
                        'user_name' => $booking->user->name,
                        'booking_time' => $booking->booking_time,
                        'booking_date' => $booking->booking_date,
                    ];
                });

            $destination->bookings = $bookings;
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

    /**
     * Get a destination by name
     */
    public function getByName($name)
    {
        $destination = BusinessProfile::where('business_name', 'LIKE', "%$name%")
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })->get();

        if ($destination->isEmpty()) {
            return ApiResponseService::error('No destination found.', null, 200);
        }

        foreach ($destination as $dest) {
            $reviews = UserActivity::where('business_user_id', $dest->user_id)
                ->where('activity_type_id', 3)
                ->with('user')
                ->get()
                ->map(function ($review) {
                    return [
                        'review_value' => $review->activity_value,
                        'user_name' => $review->user->name ?? 'Unknown',
                    ];
                });

            $rating = UserActivity::where('business_user_id', $dest->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $dest->reviews = $reviews;
            $dest->rating = $rating;

            $bookings = Booking::where('business_user_id', $dest->user_id)
                ->get()
                ->map(function ($booking) {
                    return [
                        'user_name' => $booking->user->name,
                        'booking_time' => $booking->booking_time,
                        'booking_date' => $booking->booking_date,
                    ];
                });

            $dest->bookings = $bookings;
        }

        return ApiResponseService::success('Destination retrieved successfully', $destination);
    }

    /**
     * Get a destination by its ID
     */
    public function getByUserId($userId)
    {
        $destination = BusinessProfile::where('user_id', $userId)
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })->get();

        if ($destination->isEmpty()) {
            return ApiResponseService::error('No destination found.', null, 200);
        }

        foreach ($destination as $dest) {
            $reviews = UserActivity::where('business_user_id', $dest->user_id)
                ->where('activity_type_id', 3)
                ->with('user')
                ->get()
                ->map(function ($review) {
                    return [
                        'review_value' => $review->activity_value,
                        'user_name' => $review->user->name ?? 'Unknown',
                    ];
                });

            $rating = UserActivity::where('business_user_id', $dest->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $dest->reviews = $reviews;
            $dest->rating = $rating;

            $bookings = Booking::where('business_user_id', $dest->user_id)
                ->get()
                ->map(function ($booking) {
                    return [
                        'user_name' => $booking->user->name,
                        'booking_time' => $booking->booking_time,
                        'booking_date' => $booking->booking_date,
                    ];
                });

            $dest->bookings = $bookings;
        }

        return ApiResponseService::success('Destination retrieved successfully', $destination);
    }

    /**
     * Get destinations by category (ID or name)
     */
    public function getByCategory($category)
    {
        if (is_numeric($category)) {
            $destinations = BusinessProfile::where('category_id', $category)
                ->whereHas('user', function ($query) {
                    $query->where('status', 'active');
                })->get();
        } else {
            $category = ucfirst($category);
            $categoryModel = Category::where('name', 'LIKE', "%$category%")->first();
            if ($categoryModel) {
                $destinations = BusinessProfile::where('category_id', $categoryModel->id)
                    ->whereHas('user', function ($query) {
                        $query->where('status', 'active');
                    })->get();
            } else {
                return ApiResponseService::error('Category not found', null, 404);
            }
        }

        if ($destinations->isEmpty()) {
            return ApiResponseService::error('No destinations found.', null, 200);
        }

        foreach ($destinations as $destination) {
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

            $rating = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $destination->reviews = $reviews;
            $destination->rating = $rating;

            $bookings = Booking::where('business_user_id', $destination->user_id)
                ->get()
                ->map(function ($booking) {
                    return [
                        'user_name' => $booking->user->name,
                        'booking_time' => $booking->booking_time,
                        'booking_date' => $booking->booking_date,
                    ];
                });

            $destination->bookings = $bookings;
        }

        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }

    /**
     * Get destinations by district
     */
    public function getByDistrict($district)
    {
        $destinations = BusinessProfile::where('district', 'ILIKE', "%$district%")
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })->get();

        if ($destinations->isEmpty()) {
            return ApiResponseService::error('No destinations found.', null, 200);
        }

        foreach ($destinations as $destination) {
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

            $rating = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $destination->reviews = $reviews;
            $destination->rating = $rating;

            $bookings = Booking::where('business_user_id', $destination->user_id)
                ->get()
                ->map(function ($booking) {
                    return [
                        'user_name' => $booking->user->name,
                        'booking_time' => $booking->booking_time,
                        'booking_date' => $booking->booking_date,
                    ];
                });

            $destination->bookings = $bookings;
        }

        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }

    /**
     * Get all bookings of a business user by their business_user_id
     */
    public function getBookingsBusiness($businessUserId)
    {
        // Fetch all bookings for the given business_user_id
        $bookings = Booking::where('business_user_id', $businessUserId)->get();

        if ($bookings->isEmpty()) {
            return ApiResponseService::error('No bookings found for this business user.', null, 404);
        }

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
            ->with('user')
            ->get()
            ->map(function ($review) {
                return [
                    'review_value' => $review->activity_value,
                    'user_name' => $review->user->name ?? 'Unknown',
                ];
            });

        if ($reviews->isEmpty()) {
            return ApiResponseService::error('No reviews found for this business user.', null, 204);
        }

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
            ->map(function ($value) {
                return (float) $value;
            })
            ->avg() ?? 0; // Set to 0 if no ratings

        return ApiResponseService::success('Rating retrieved successfully', ['rating' => $rating]);
    }
}
