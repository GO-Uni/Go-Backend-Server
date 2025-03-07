<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'category_id',
        'district',
        'latitude',
        'longitude',
        'opening_hour',
        'closing_hour',
        'main_img',
        'description',
        'counter_booking',
    ];

    public $timestamps = false;

    protected $appends = [
        'user_name',
        'category_name',
        'available_booking_slots',
    ];

    protected $hidden = [
        'category',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the category associated with the business profile.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Scopes

    /**
     * Scope a query to filter business profiles by district.
     */
    public function scopeByDistrict($query, $district)
    {
        return $query->where('district', $district);
    }

    /**
     * Scope a query to filter business profiles by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Accessors

    /**
     * Get the user's name associated with the business profile.
     */
    public function getUserNameAttribute()
    {
        return User::find($this->user_id)->name ?? null;
    }

    /**
     * Get the category's name associated with the business profile.
     */
    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->name : null;
    }

    /**
     * Get the available booking slots for the business profile.
     */
    public function getAvailableBookingSlotsAttribute()
    {
        try {
            $openingHour = Carbon::createFromFormat('H:i', substr($this->opening_hour, 0, 5));
            $closingHour = Carbon::createFromFormat('H:i', substr($this->closing_hour, 0, 5));

            $slots = [];
            while ($openingHour->lt($closingHour)) {
                $slots[] = $openingHour->format('H:i');
                $openingHour->addHour();
            }

            return $slots;
        } catch (InvalidFormatException $e) {
            return [];
        }
    }
}
