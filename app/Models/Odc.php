<?php

namespace App\Models;

use Database\Factories\OdcFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Odc extends Model
{
    /** @use HasFactory<OdcFactory> */
    use HasFactory;

    protected $fillable = [
        'kode_odc',
        'nama_odc',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
