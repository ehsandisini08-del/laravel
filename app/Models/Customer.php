<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'name',
        'address',
        'phone',
        'latitude',
        'longitude',
        'area_id',
        'router_id',
        'package_id',
        'ppp_secret_id',
        'ppp_username',
        'ppp_password',
        'installation_date',
        'due_day',
        'isolation_day',
        'status',
        'service_status',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'installation_date' => 'date',
        'isolation_day' => 'integer',
        'due_day' => 'integer',
        'service_status' => ServiceStatus::class,
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function pppSecret(): BelongsTo
    {
        return $this->belongsTo(PppSecret::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isolationLogs(): HasMany
    {
        return $this->hasMany(IsolationLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', CustomerStatus::Active);
    }

    public function scopeIsolated($query)
    {
        return $query->where('status', CustomerStatus::Isolated);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', CustomerStatus::Suspended);
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', CustomerStatus::Terminated);
    }

    public function isActive(): bool
    {
        return $this->status === CustomerStatus::Active->value;
    }

    public function isIsolated(): bool
    {
        return $this->status === CustomerStatus::Isolated->value;
    }

    public function isSuspended(): bool
    {
        return $this->status === CustomerStatus::Suspended->value;
    }

    public function isTerminated(): bool
    {
        return $this->status === CustomerStatus::Terminated->value;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            CustomerStatus::Active->value => 'Active',
            CustomerStatus::Isolated->value => 'Isolated',
            CustomerStatus::Suspended->value => 'Suspended',
            CustomerStatus::Terminated->value => 'Terminated',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            CustomerStatus::Active->value => 'success',
            CustomerStatus::Isolated->value => 'danger',
            CustomerStatus::Suspended->value => 'warning',
            CustomerStatus::Terminated->value => 'default',
            default => 'default',
        };
    }
}
