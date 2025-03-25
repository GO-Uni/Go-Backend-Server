<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use App\Services\ApiResponseService;
use App\Models\SavedDestination;

class UserController extends Controller
{
    /**
     * Get all bookings of a user 
     */
    public function getUserBookings($userId)
    {
        // Fetch all bookings
        $bookings = Booking::where('user_id', $userId)
            ->with(['businessUser.businessProfile'])
            ->get()
            ->map(function ($booking) {
                return [
                    'business_name' => $booking->businessUser->businessProfile->business_name ?? 'Unknown',
                    'booking_time' => $booking->booking_time,
                    'booking_date' => $booking->booking_date,
                ];
            });

        if ($bookings->isEmpty()) {
            return ApiResponseService::error('No bookings found for this user.', null, 404);
        }

        return ApiResponseService::success('Bookings retrieved successfully', $bookings);
    }

    /**
     * Get all saved destinations of a user
     */
    public function getSavedDestinations($userId)
    {
        // Fetch all saved destinations
        $savedDestinations = SavedDestination::where('user_id', $userId)
            ->with(['businessUser'])
            ->get()
            ->map(function ($destination) {
                return [
                    'business_user_name' => $destination->businessUser->name ?? 'Unknown',
                    'destination_id' => $destination->id,
                ];
            });

        if ($savedDestinations->isEmpty()) {
            return ApiResponseService::error('No saved destinations found for this user.', null, 404);
        }

        return ApiResponseService::success('Saved destinations retrieved successfully', $savedDestinations);
    }
}
