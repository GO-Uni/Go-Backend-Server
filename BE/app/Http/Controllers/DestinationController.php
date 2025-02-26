<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Services\ApiResponseService;

class DestinationController extends Controller
{
    /**
     * Get all destinations
     */
    public function index()
    {
        $destinations = BusinessProfile::all();
        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }

    /**
     * Get a destination by name
     */
    public function getByName($name)
    {
        $name = ucfirst($name); // Ensure the first letter is uppercase
        $destination = BusinessProfile::where('business_name', 'LIKE', "%$name%")->get();
        return ApiResponseService::success('Destination retrieved successfully', $destination);
    }

    /**
     * Get destinations by category (ID or name)
     */
    public function getByCategory($category)
    {
        // Check if the category is ID or name
        if (is_numeric($category)) {
            $destinations = BusinessProfile::where('category_id', $category)->get();
        } else {
            $category = ucfirst($category); 
            $categoryModel = Category::where('name', 'LIKE', "%$category%")->first();
            if ($categoryModel) {
                $destinations = BusinessProfile::where('category_id', $categoryModel->id)->get();
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
        $destinations = BusinessProfile::where('district', 'ILIKE', "%$district%")->get();
        return ApiResponseService::success('Destinations retrieved successfully', $destinations);
    }
}
