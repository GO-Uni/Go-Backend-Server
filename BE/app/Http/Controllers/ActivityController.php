<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ApiResponseService;
use App\Models\SavedDestination;
use App\Models\UserActivity;
use App\Models\ActivityType;
use App\Models\BusinessProfile;
use App\Models\Booking;
use Carbon\Carbon;

class ActivityController extends Controller
{
    /**
     * Save a destination.
     */
    public function saveDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
        ]);

        // Check if the destination is already saved
        $existingSavedDestination = SavedDestination::where('user_id', $user->id)
            ->where('business_user_id', $request->business_user_id)
            ->first();

        if ($existingSavedDestination) {
            return ApiResponseService::error('Destination already saved.', null, 400);
        }

        // Getting the category name of destination
        $businessProfile = BusinessProfile::where('user_id', $request->business_user_id)->first();
        if (!$businessProfile) {
            return ApiResponseService::error('Business profile not found.', null, 404);
        }
        $categoryName = $businessProfile->category->name;

        $savedDestination = SavedDestination::create([
            'user_id' => $user->id,
            'business_user_id' => $request->business_user_id,
        ]);

        $activityType = ActivityType::where('name', 'save')->first();
        UserActivity::create([
            'user_id' => $user->id,
            'business_user_id' => $request->business_user_id,
            'activity_type_id' => $activityType->id,
            'activity_value' => null,
            'category' => $categoryName,
        ]);

        return ApiResponseService::success('Destination saved successfully', ['saved_destination' => $savedDestination]);
    }

    /**
     * Unsave a destination.
     */
    public function unsaveDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
        ]);

        // Find the saved destination record
        $savedDestination = SavedDestination::where('user_id', $user->id)
            ->where('business_user_id', $request->business_user_id)
            ->first();

        if (!$savedDestination) {
            return ApiResponseService::error('Saved destination not found.', null, 404);
        }

        // Delete the saved destination record
        $savedDestination->delete();

        $activityType = ActivityType::where('name', 'save')->first();
        UserActivity::where('user_id', $user->id)
            ->where('business_user_id', $request->business_user_id)
            ->where('activity_type_id', $activityType->id)
            ->delete();

        return ApiResponseService::success('Destination unsaved successfully');
    }

    /**
     * Rate a destination.
     */
    public function rateDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
            'rating' => ['required', 'regex:/^(0(\.5)?|[1-4](\.5)?|5)$/'],
        ]);

        // Getting the category name of the destination
        $businessProfile = BusinessProfile::where('user_id', $request->business_user_id)->first();
        if (!$businessProfile) {
            return ApiResponseService::error('Business profile not found.', null, 404);
        }

        $categoryName = $businessProfile->category->name;

        // Get the activity type for "rate"
        $activityType = ActivityType::where('name', 'rate')->first();

        // Create or Update record
        $userActivity = UserActivity::updateOrCreate(
            [
                'user_id' => $user->id,
                'business_user_id' => $request->business_user_id,
                'activity_type_id' => $activityType->id,
            ],
            [
                'activity_value' => (string) $request->rating,
                'category' => $categoryName,
            ]
        );

        return ApiResponseService::success('Destination rated successfully', ['user_activity' => $userActivity]);
    }

    /**
     * Review a destination.
     */
    public function reviewDestination(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
            'review' => 'required|string',
        ]);

        // Getting the category name of the destination
        $businessProfile = BusinessProfile::where('user_id', $request->business_user_id)->first();
        if (!$businessProfile) {
            return ApiResponseService::error('Business profile not found.', null, 404);
        }

        $categoryName = $businessProfile->category->name;

        $activityType = ActivityType::where('name', 'review')->first();
        $userActivity = UserActivity::create([
            'user_id' => $user->id,
            'business_user_id' => $request->business_user_id,
            'activity_type_id' => $activityType->id,
            'activity_value' => $request->review,
            'category' => $categoryName,
        ]);

        return ApiResponseService::success('Destination reviewed successfully', ['user_activity' => $userActivity]);
    }

    /**
     * Book a slot for a destination.
     */
    public function bookSlot(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'business_user_id' => 'required|integer|exists:users,id',
            'booking_time' => 'required|date_format:H:i',
            'booking_date' => 'required|date_format:Y-m-d',
        ]);

        $businessProfile = BusinessProfile::where('user_id', $request->business_user_id)->first();
        if (!$businessProfile) {
            return ApiResponseService::error('Business profile not found.', null, 404);
        }

        // Check if the booking time is within the opening and closing hours
        $openingHour = Carbon::createFromFormat('H:i', substr($businessProfile->opening_hour, 0, 5));
        $closingHour = Carbon::createFromFormat('H:i', substr($businessProfile->closing_hour, 0, 5));
        $bookingTime = Carbon::createFromFormat('H:i', $request->booking_time);

        if ($bookingTime->lt($openingHour) || $bookingTime->gte($closingHour)) {
            return ApiResponseService::error('Booking time is outside of business hours.', null, 400);
        }

        // Check the number of bookings for the given time slot on the specified date
        $existingBookings = Booking::where('business_user_id', $request->business_user_id)
            ->where('booking_time', $request->booking_time)
            ->where('booking_date', $request->booking_date)
            ->count();

        if ($existingBookings >= $businessProfile->counter_booking) {
            return ApiResponseService::error('No available slots for the selected time.', null, 400);
        }

        // Create a new booking
        $booking = Booking::create([
            'user_id' => $user->id,
            'business_user_id' => $request->business_user_id,
            'booking_time' => $request->booking_time,
            'booking_date' => $request->booking_date,
        ]);

        // Check if this was the last available slot
        $remainingSlots = $businessProfile->counter_booking - $existingBookings - 1;
        $isLastSlot = $remainingSlots <= 0;

        return ApiResponseService::success('Slot booked successfully', [
            'booking' => $booking,
            'is_last_slot' => $isLastSlot,
        ]);
    }
}
