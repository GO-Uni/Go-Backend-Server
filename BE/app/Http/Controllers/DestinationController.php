<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\UserActivity;
use App\Services\ApiResponseService;

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

        foreach ($destinations as $destination) {
            // List all review values
            $reviews = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 3)
                ->pluck('activity_value');

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
        }

        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }

    /**
     * Get all destinations grouped by user status
     */
    public function getGroupedByStatus()
    {
        $activeDestinations = BusinessProfile::whereHas('user', function ($query) {
            $query->where('status', 'active');
        })->get();

        $bannedDestinations = BusinessProfile::whereHas('user', function ($query) {
            $query->where('status', 'banned');
        })->get();

        foreach ($activeDestinations as $destination) {
            $reviews = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 3)
                ->pluck('activity_value');

            $rating = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $destination->reviews = $reviews;
            $destination->rating = $rating;
        }

        foreach ($bannedDestinations as $destination) {
            $reviews = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 3)
                ->pluck('activity_value');

            $rating = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $destination->reviews = $reviews;
            $destination->rating = $rating;
        }

        $destinations = [
            'active' => $activeDestinations,
            'banned' => $bannedDestinations,
        ];

        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }

    /**
     * Get a destination by name
     */
    public function getByName($name)
    {
        $name = ucfirst($name);
        $destination = BusinessProfile::where('business_name', 'LIKE', "%$name%")
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })->get();

        foreach ($destination as $dest) {
            $reviews = UserActivity::where('business_user_id', $dest->user_id)
                ->where('activity_type_id', 3)
                ->pluck('activity_value');

            $rating = UserActivity::where('business_user_id', $dest->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $dest->reviews = $reviews;
            $dest->rating = $rating;
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

        foreach ($destinations as $destination) {
            $reviews = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 3)
                ->pluck('activity_value');

            $rating = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $destination->reviews = $reviews;
            $destination->rating = $rating;
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

        foreach ($destinations as $destination) {
            $reviews = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 3)
                ->pluck('activity_value');

            $rating = UserActivity::where('business_user_id', $destination->user_id)
                ->where('activity_type_id', 2)
                ->pluck('activity_value')
                ->map(function ($value) {
                    return (float) $value;
                })
                ->avg() ?? 0;

            $destination->reviews = $reviews;
            $destination->rating = $rating;
        }

        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }
}
