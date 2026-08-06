<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    public const TYPE_CUSTOMER = 'customer';

    public const TYPE_ADMIN = 'admin';

    protected $fillable = [
        'user_type',
        'user_id',
        'token',
        'platform',
        'device_name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function scopeForUser($query, string $userType, int $userId)
    {
        return $query->where('user_type', $userType)->where('user_id', $userId);
    }
}
