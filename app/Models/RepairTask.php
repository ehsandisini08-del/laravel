<?php

namespace App\Models;

use App\Enums\RepairTaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        return $this->isProses() && $this->taken_by_user_id === $user->id;
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
