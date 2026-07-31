<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'router_id',
        'ppp_profile_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function pppProfile(): BelongsTo
    {
        return $this->belongsTo(PppProfile::class);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'package_area');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? '🟢 Active' : '🔴 Inactive';
    }

    public function getPriceFormattedAttribute(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
