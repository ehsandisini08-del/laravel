<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cpe extends Model
{
    use HasFactory;

    public const STATUS_ONLINE = 'online';

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_UNKNOWN = 'unknown';

    protected $fillable = [
        'genieacs_id',
        'customer_id',
        'ppp_username',
        'serial_number',
        'manufacturer',
        'model_name',
        'model_number',
        'hardware_version',
        'software_version',
        'ip_address',
        'mac_address',
        'status',
        'last_inform_at',
        'uptime',
        'signal_parameters',
        'tags',
        'synced_at',
    ];

    protected $casts = [
        'last_inform_at' => 'datetime',
        'signal_parameters' => 'array',
        'tags' => 'array',
        'synced_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    public function scopeOffline($query)
    {
        return $query->where('status', self::STATUS_OFFLINE);
    }

    public function scopeUnlinked($query)
    {
        return $query->whereNull('customer_id');
    }

    public function scopeSearch($query, ?string $search)
    {
        if (blank($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('serial_number', 'like', "%{$search}%")
                ->orWhere('ppp_username', 'like', "%{$search}%")
                ->orWhere('model_name', 'like', "%{$search}%")
                ->orWhere('manufacturer', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhere('genieacs_id', 'like', "%{$search}%");
        });
    }

    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ONLINE;
    }

    public function isLinked(): bool
    {
        return $this->customer_id !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ONLINE => 'Online',
            self::STATUS_OFFLINE => 'Offline',
            default => 'Unknown',
        };
    }

    public function getFormattedUptimeAttribute(): string
    {
        if ($this->uptime === null) {
            return '-';
        }

        $seconds = (int) $this->uptime;
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days} hari";
        }

        if ($hours > 0) {
            $parts[] = "{$hours} jam";
        }

        if ($minutes > 0 && $days === 0) {
            $parts[] = "{$minutes} menit";
        }

        return $parts !== [] ? implode(' ', $parts) : 'Baru saja';
    }

    /**
     * RX Power value from the signal parameters snapshot (e.g. VirtualParameters.RXPower).
     */
    public function getRxPowerAttribute(): ?string
    {
        if (empty($this->signal_parameters)) {
            return null;
        }

        foreach ($this->signal_parameters as $path => $parameter) {
            $name = strtolower((string) ($parameter['label'] ?? $path));

            if (str_contains($name, 'rxpower') || str_contains($name, 'rx_power') || str_contains($name, 'ont_rx')) {
                return $parameter['value'] ?? null;
            }
        }

        return null;
    }
}
