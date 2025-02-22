<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_user_id', 
        'type',             
        'start_date',       
        'end_date',        
        'active',           
    ];

    protected $casts = [
        'start_date' => 'date', 
        'end_date' => 'date', 
        'active' => 'boolean', 
    ];

    protected $appends = [
        'status',              
        'duration_in_days',    
        'business_account_name',
    ];

    /**
     * The allowed values for the subscription type.
    */
    public static $types = [
        'none',   
        'monthly', 
        'yearly',  
    ];

    // Relationships

    /**
     * Get the business user associated with the subscription.
    */
    public function businessUser()
    {
        return $this->belongsTo(User::class, 'business_user_id');
    }

    // Scopes

    /**
     * Scope a query to only include active subscriptions.
    */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include subscriptions of a specific type.
    */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors

    /**
     * Get the subscription status as a string.
    */
    public function getStatusAttribute()
    {
        return $this->active ? 'Active' : 'Inactive';
    }

    /**
     * Get the subscription duration in days.
    */
    public function getDurationInDaysAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        return null;
    }

    /**
     * Get the business account name associated with the subscription.
    */
    public function getBusinessAccountNameAttribute()
    {
        return $this->businessUser ? $this->businessUser->name : null;
    }

}
