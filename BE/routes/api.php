<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityController;
use App\Http\Middleware\CheckBusinessAuthorization;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserActivityController;

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
    Route::post('/activity/book', [ActivityController::class, 'bookSlot']);
});

// Categories
Route::get('/categories', [CategoryController::class, 'index']);

// Destinations
Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/grouped', [DestinationController::class, 'getGroupedByStatus']);
Route::get('/destinations/name/{name}', [DestinationController::class, 'getByName']);
Route::get('/destinations/{userId}', [DestinationController::class, 'getByUserId']);
Route::get('/destinations/category/{category}', [DestinationController::class, 'getByCategory']);
Route::get('/destinations/district/{district}', [DestinationController::class, 'getByDistrict']);
Route::get('/destinations/bookings/{businessUserId}', [DestinationController::class, 'getBookingsBusiness']);
Route::get('/destinations/reviews/{businessUserId}', [DestinationController::class, 'getReviews']);
Route::get('/destinations/rating/{businessUserId}', [DestinationController::class, 'getRating']);


// User
Route::get('/user/{userId}/bookings', [UserController::class, 'getUserBookings']);
Route::get('/user/{userId}/saved', [UserController::class, 'getSavedDestinations']);


// Recommendation 
Route::get('/recommend-destinations/{userId}', [UserActivityController::class, 'recommendDestinations']);
