<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_profile_id', 
        'path_name',          
    ];

     // Relationships

    /**
     * Get the business profile associated with the image.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    // Scopes

    /**
     * Scope a query to filter images by business profile ID.
     */
    public function scopeByBusinessProfile($query, $businessProfileId)
    {
        return $query->where('business_profile_id', $businessProfileId);
    }
}
