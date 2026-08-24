<?php

namespace App\Models;

use App\Enums\RepairTaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'assigned_by_user_id',
        'taken_by_user_id',
        'nama_customer',
        'alamat',
        'latitude',
        'longitude',
        'no_telp',
        'keterangan',
        'keterangan_teknisi',
        'status',
        'foto_bukti',
        'taken_at',
        'completed_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => RepairTaskStatus::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function takenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by_user_id');
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'repair_task_user')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RepairTaskComment::class)->orderBy('created_at');
    }

    public function scopeBaru($query)
    {
        return $query->where('status', RepairTaskStatus::Baru);
    }

    public function scopeProses($query)
    {
        return $query->where('status', RepairTaskStatus::Proses);
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', RepairTaskStatus::Selesai);
    }

    public function isBaru(): bool
    {
        return $this->status === RepairTaskStatus::Baru;
    }

    public function isProses(): bool
    {
        return $this->status === RepairTaskStatus::Proses;
    }

    public function isSelesai(): bool
    {
        return $this->status === RepairTaskStatus::Selesai;
    }

    public function canBeTakenBy(User $user): bool
    {
        return $this->isBaru() && $user->isTeknisi();
    }

    public function canBeCompletedBy(User $user): bool
    {
        if (! $this->isProses()) {
            return false;
        }

        if ($this->taken_by_user_id === $user->id) {
            return true;
        }

        if ($this->relationLoaded('technicians')) {
            return $this->technicians->contains('id', $user->id);
        }

        return $this->technicians()->where('users.id', $user->id)->exists();
    }

    public function getAllTechniciansNamesAttribute(): string
    {
        if ($this->relationLoaded('technicians') && $this->technicians->isNotEmpty()) {
            return $this->technicians->pluck('name')->join(', ');
        }

        $names = $this->technicians()->pluck('name')->all();
        if (! empty($names)) {
            return implode(', ', $names);
        }

        return $this->takenBy?->name ?? '-';
    }

    public function getMapsLinkAttribute(): string
    {
        if ($this->latitude && $this->longitude) {
            return "https://maps.google.com/?q={$this->latitude},{$this->longitude}";
        }

        return '#';
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status->badge();
    }
}
