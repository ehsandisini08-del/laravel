<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PppProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'mikrotik_id',
        'name',
        'local_address',
        'remote_address',
        'dns_server',
        'rate_limit',
        'parent_queue',
        'only_one',
        'change_tcp_mss',
        'use_compression',
        'use_encryption',
        'use_ipv6',
        'bridge',
        'bridge_path_cost',
        'bridge_horizon',
        'comment',
        'synced_at',
    ];

    protected $casts = [
        'only_one' => 'boolean',
        'change_tcp_mss' => 'boolean',
        'use_compression' => 'boolean',
        'use_encryption' => 'boolean',
        'use_ipv6' => 'boolean',
        'bridge_path_cost' => 'integer',
        'synced_at' => 'datetime',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeForRouter($query, $routerId)
    {
        return $query->where('router_id', $routerId);
    }

    public function scopeSynced($query)
    {
        return $query->whereNotNull('synced_at');
    }

    public function getRateLimitDisplayAttribute(): string
    {
        return $this->rate_limit ?: '-';
    }

    public function getOnlyOneDisplayAttribute(): string
    {
        return $this->only_one ? 'Yes' : 'No';
    }

    public function getEncryptionDisplayAttribute(): string
    {
        return $this->use_encryption ? 'Yes' : 'No';
    }
}
