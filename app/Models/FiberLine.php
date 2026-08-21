<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiberLine extends Model
{
    protected $fillable = [
        'nama',
        'tipe_kabel',
        'source_type',
        'source_id',
        'destination_type',
        'destination_id',
        'geometry',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'destination_id' => 'integer',
    ];

    const STATUS_ACTIVE = 'ACTIVE';

    const STATUS_INACTIVE = 'INACTIVE';

    const STATUS_DAMAGE = 'DAMAGE';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_DAMAGE => 'Damage',
        ];
    }

    /**
     * Get geometry as decoded array.
     *
     * @return array<string, mixed>|null
     */
    public function getGeometryArrayAttribute(): ?array
    {
        if (empty($this->geometry)) {
            return null;
        }

        return json_decode($this->geometry, true);
    }

    /**
     * Get polyline coordinates for Leaflet [[lat,lng], ...].
     *
     * @return array<int, array{float, float}>
     */
    public function getLeafletCoordsAttribute(): array
    {
        $geo = $this->geometry_array;
        if (! $geo || ! isset($geo['coordinates'])) {
            return [];
        }

        return array_map(
            fn ($c) => [$c[1], $c[0]], // GeoJSON is [lng,lat], Leaflet wants [lat,lng]
            $geo['coordinates']
        );
    }

    public function getSourceModelAttribute(): ?Model
    {
        return match ($this->source_type) {
            'odc' => Odc::find($this->source_id),
            'odp' => Odp::find($this->source_id),
            default => null,
        };
    }

    public function getDestinationModelAttribute(): ?Model
    {
        return match ($this->destination_type) {
            'odc' => Odc::find($this->destination_id),
            'odp' => Odp::find($this->destination_id),
            'customer' => Customer::find($this->destination_id),
            default => null,
        };
    }
}
