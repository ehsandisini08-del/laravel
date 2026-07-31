<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaDevice extends Model
{
    use HasFactory;

    protected $table = 'wa_devices';

    protected $fillable = [
        'device_name',
        'session_name',
        'phone_number',
        'profile_name',
        'profile_picture',
        'status',
        'last_seen',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(WaMessage::class, 'device_id');
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isDisconnected(): bool
    {
        return $this->status === 'disconnected';
    }

    public function isQrWaiting(): bool
    {
        return $this->status === 'qr_waiting';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'connected' => 'success',
            'disconnected' => 'danger',
            'connecting' => 'warning',
            'qr_waiting' => 'info',
            'logged_out' => 'default',
            default => 'default',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'connected' => 'Connected',
            'disconnected' => 'Disconnected',
            'connecting' => 'Connecting',
            'qr_waiting' => 'QR Waiting',
            'logged_out' => 'Logged Out',
            default => $this->status,
        };
    }
}
