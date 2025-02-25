<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Services\ApiResponseService;

class CategoryController extends Controller
{
    /**
     * Get all categories.
     */
    public function index()
    {
        $categories = Category::all();
        return ApiResponseService::success('Categories retrieved successfully', $categories);
    }
}
