<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odp extends Model
{
    protected $fillable = [
        'odc_id',
        'kode',
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'kapasitas',
        'port_terpakai',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'kapasitas' => 'integer',
        'port_terpakai' => 'integer',
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

    public function getPortAvailableAttribute(): int
    {
        return max(0, ($this->kapasitas ?? 0) - ($this->port_terpakai ?? 0));
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

    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Get detailed port list with customer info.
     *
     * @return array<int, array{port: int, status: string, customer: Customer|null}>
     */
    public function getPortDetailAttribute(): array
    {
        $customersByPort = $this->customers()
            ->whereNotNull('port_odp')
            ->get()
            ->keyBy('port_odp');

        $ports = [];
        for ($i = 1; $i <= ($this->kapasitas ?? 16); $i++) {
            $customer = $customersByPort->get($i);
            $ports[] = [
                'port' => $i,
                'status' => $customer ? 'USED' : 'AVAILABLE',
                'customer' => $customer,
            ];
        }

        return $ports;
    }
}
