<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'path_name',
        'is_3d',
    ];

    // Relationships    
    public function user()
    {
        return $this->belongsTo(User::class);
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
