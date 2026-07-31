<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PppSecret extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'mikrotik_id',
        'name',
        'password',
        'service',
        'profile',
        'local_address',
        'remote_address',
        'caller_id',
        'disabled',
        'comment',
        'last_logged_out',
    ];

    protected $casts = [
        'disabled' => 'boolean',
        'last_logged_out' => 'datetime',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeActive($query)
    {
        return $query->where('disabled', false);
    }

    public function scopeDisabled($query)
    {
        return $query->where('disabled', true);
    }

    public function scopeForRouter($query, $routerId)
    {
        return $query->where('router_id', $routerId);
    }

    public function isActive(): bool
    {
        return ! $this->disabled;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->disabled ? '🔴 Disabled' : '🟢 Active';
    }
}
