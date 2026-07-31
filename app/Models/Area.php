<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? '🟢 Active' : '🔴 Inactive';
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_area');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
