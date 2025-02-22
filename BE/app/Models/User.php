<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_img',
        'role_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = ['role_name'];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Accessor for role name
    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->name : null;
    }
}
