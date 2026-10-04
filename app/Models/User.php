<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $fillable = [
        'name',
        'ci',
        'username',
        'email',
        'phone',
        'address',
        'gender',
        'sales_commission',
        'branch',
        'avatar',
        'password',
        'pin_code',
        'role',
        'status',
    ];

    protected $hidden = ['password', 'pin_code'];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }
        return asset('storage/' . $this->avatar);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
