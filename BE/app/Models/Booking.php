<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',         
        'business_user_id', 
        'booking_time',     
    ];

    protected $casts = [
        'booking_time' => 'datetime', 
    ];

    // Relationships

    /**
     * Get user 
    */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the business user
    */
    public function businessUser()
    {
        return $this->belongsTo(User::class, 'business_user_id');
    }

    // Scopes

    /**
     * Scope a query to filter bookings by user ID.
    */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter bookings by business user ID.
    */
    public function scopeByBusinessUser($query, $businessUserId)
    {
        return $query->where('business_user_id', $businessUserId);
    }

    /**
     * Scope a query to filter bookings by a specific date.
    */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('booking_time', $date);
    }

    // Accessors

    /**
     * Get the formatted booking time (e.g., "March 3, 2025 12:00 PM").
    */
    public function getFormattedBookingTimeAttribute()
    {
        return $this->booking_time ? $this->booking_time->format('F j, Y h:i A') : null;
    }

    /**
     * Get the user's name 
    */
    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : null;
    }

    /**
     * Get the business user's name 
    */
    public function getBusinessUserNameAttribute()
    {
        return $this->businessUser ? $this->businessUser->name : null;
    }
}
