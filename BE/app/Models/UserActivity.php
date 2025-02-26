<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',           
        'business_user_id',  
        'category',          
        'activity_type_id', 
        'activity_value',   
    ];

    protected $casts = [
        'activity_value' => 'string', 
        'created_at' => 'datetime',  
    ];

    protected $appends = [
        'activity_type_name',
    ];

    public $timestamps = false;

    // Relationships

    /**
     * Get the user who performed the activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the business user associated with the activity.
     */
    public function businessUser()
    {
        return $this->belongsTo(User::class, 'business_user_id');
    }

    /**
     * Get the activity type associated with the activity.
     */
    public function activityType()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    // Scopes

    /**
     * Scope a query to filter activities by user ID.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter activities by business user ID.
     */
    public function scopeByBusinessUser($query, $businessUserId)
    {
        return $query->where('business_user_id', $businessUserId);
    }

    /**
     * Scope a query to filter activities by activity type ID.
     */
    public function scopeByActivityType($query, $activityTypeId)
    {
        return $query->where('activity_type_id', $activityTypeId);
    }

     // Accessors

    /**
     * Get the activity type name.
     */
    public function getActivityTypeNameAttribute()
    {
        return $this->activityType ? $this->activityType->name : null;
    }

}
