<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationReport extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'installation_date',
        'port_odp',
        'rx_power',
        'modem_serial',
        'device_name',
        'parts_used',
        'photo_path',
        'notes',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
