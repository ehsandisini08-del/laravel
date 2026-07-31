<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Router extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'host',
        'api_port',
        'api_ssl',
        'username',
        'password',
        'location',
        'timezone',
        'routeros_version',
        'board_name',
        'identity',
        'architecture',
        'cpu',
        'total_memory',
        'free_memory',
        'uptime',
        'last_seen_at',
        'status',
        'enabled',
        'is_default',
        'priority',
    ];

    protected $casts = [
        'api_port' => 'integer',
        'api_ssl' => 'boolean',
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'total_memory' => 'integer',
        'free_memory' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Router $router) {
            if (empty($router->uuid)) {
                $router->uuid = (string) Str::uuid();
            }

            if ($router->is_default) {
                static::where('is_default', true)->update(['is_default' => false]);
            }
        });

        static::updating(function (Router $router) {
            if ($router->is_default && $router->isDirty('is_default')) {
                static::where('id', '!=', $router->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    public function getDecryptedPasswordAttribute(): string
    {
        return Crypt::decryptString($this->attributes['password']);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    public function isChecking(): bool
    {
        return $this->status === 'checking';
    }

    public function markAsOnline(): void
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }

    public function markAsOffline(): void
    {
        $this->update([
            'status' => 'offline',
        ]);
    }

    public function markAsChecking(): void
    {
        $this->update([
            'status' => 'checking',
        ]);
    }

    public function pppSecrets(): HasMany
    {
        return $this->hasMany(PppSecret::class);
    }

    public function pppProfiles(): HasMany
    {
        return $this->hasMany(PppProfile::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
