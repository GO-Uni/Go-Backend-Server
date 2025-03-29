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
        $savedDestinations = SavedDestination::where('user_id', $userId)->get();

        if ($savedDestinations->isEmpty()) {
            return ApiResponseService::error('No saved destinations found for this user.', null, 404);
        }

        // Initialize the DestinationController
        $destinationController = app(\App\Http\Controllers\DestinationController::class);

        // Map saved destinations and trigger the getByUserId method 
        $detailedDestinations = $savedDestinations->map(function ($destination) use ($destinationController) {
            $response = $destinationController->getByUserId($destination->business_user_id);

            $responseData = json_decode($response->getContent(), true); 
            return $responseData['data'][0] ?? null; 
        });

        return ApiResponseService::success('Saved destinations retrieved successfully', $detailedDestinations);
    }
}
