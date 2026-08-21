<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odc extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'kapasitas',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'kapasitas' => 'integer',
    ];

    /** Status constants */
    const STATUS_ACTIVE = 'ACTIVE';

    const STATUS_WARNING = 'WARNING';

    const STATUS_DOWN = 'DOWN';

    const STATUS_MAINTENANCE = 'MAINTENANCE';

    const STATUS_INACTIVE = 'INACTIVE';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_WARNING => 'Warning',
            self::STATUS_DOWN => 'Down',
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_WARNING => 'yellow',
            self::STATUS_DOWN => 'red',
            self::STATUS_MAINTENANCE => 'blue',
            default => 'gray',
        };
    }

    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
