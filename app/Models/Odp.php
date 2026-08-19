<?php

namespace App\Models;

use Database\Factories\OdpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Odp extends Model
{
    /** @use HasFactory<OdpFactory> */
    use HasFactory;

    protected $fillable = [
        'odc_id',
        'kode_odp',
        'nama_odp',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }
}
