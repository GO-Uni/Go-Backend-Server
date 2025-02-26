<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessProfile;
use App\Models\Category;
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

        return ApiResponseService::success('Destination retrieved successfully', $destination);
    }

    /**
     * Get destinations by category (ID or name)
     */
    public function getByCategory($category)
    {
        // Check if the category is ID or name
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

        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }
}
