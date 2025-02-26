<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_img',
        'role_id',
        'status'
    ];

    protected $hidden = [
        'password',
        'role'
    ];

    protected $appends = ['role_name'];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class, 'user_id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'business_user_id')
            ->where('active', true);
    }

    // Accessor for role name
    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->name : null;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
