<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckBusinessAuthorization;
use App\Http\Controllers\CategoryController;

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
});

Route::get('/categories', [CategoryController::class, 'index']);
