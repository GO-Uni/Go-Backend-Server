<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityController;
use App\Http\Middleware\CheckBusinessAuthorization;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DestinationController;

// AuthController routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::put('/profile/edit', [ProfileController::class, 'updateProfile']);

    // Routes that require business authorization
    Route::middleware(CheckBusinessAuthorization::class)->group(function () {
        Route::put('/business/profile/edit', [ProfileController::class, 'updateProfile']);
        Route::put('/business/subscription/edit', [ProfileController::class, 'updateSubscription']);
    });

    // ActivityController routes
    Route::post('/activity/save', [ActivityController::class, 'saveDestination']);
    Route::post('/activity/unsave', [ActivityController::class, 'unsaveDestination']);
    Route::post('/activity/rate', [ActivityController::class, 'rateDestination']);
    Route::post('/activity/review', [ActivityController::class, 'reviewDestination']);
});

// Categories
Route::get('/categories', [CategoryController::class, 'index']);

// Destinations
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/grouped', [DestinationController::class, 'getGroupedByStatus']);
Route::get('/destinations/name/{name}', [DestinationController::class, 'getByName']);
Route::get('/destinations/category/{category}', [DestinationController::class, 'getByCategory']);
Route::get('/destinations/district/{district}', [DestinationController::class, 'getByDistrict']);
