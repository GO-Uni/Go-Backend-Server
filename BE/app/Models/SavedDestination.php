<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedDestination extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',         
        'business_user_id', 
    ];

    public $timestamps = false;

    // Relationships

    /**
     * Get the user who saved the destination.
    */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the business user representing the destination.
    */
    public function businessUser()
    {
        return $this->belongsTo(User::class, 'business_user_id');
    }

    // Scopes

    /**
     * Scope a query to filter saved destinations by user ID.
    */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter saved destinations by business user ID.
    */
    public function scopeByBusinessUser($query, $businessUserId)
    {
        return $query->where('business_user_id', $businessUserId);
    }

    // Accessors

    /**
     * Get the business user's name (destination name).
    */
    public function getDestinationNameAttribute()
    {
        return $this->businessUser ? $this->businessUser->name : null;
    }

    /**
     * Get the user's name (user who saved the destination).
    */
    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : null;
    }

}
