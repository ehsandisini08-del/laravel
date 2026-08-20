<?php

namespace App\Models;

use Database\Factories\StockTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransaction extends Model
{
    /** @use HasFactory<StockTransactionFactory> */
    use HasFactory;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const PREFIX_IN = 'BM';

    public const PREFIX_OUT = 'BK';

    public const PREFIX_ADJUSTMENT = 'SO';

    protected $fillable = [
        'transaction_number',
        'type',
        'reference',
        'supplier',
        'recipient',
        'reason',
        'notes',
        'user_id',
        'transaction_date',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'transaction_date' => 'datetime',
    ];

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransactionItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_IN => 'Barang Masuk',
            self::TYPE_OUT => 'Barang Keluar',
            self::TYPE_ADJUSTMENT => 'Stok Opname',
            default => ucfirst($type),
        };
    }

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            self::TYPE_IN => 'success',
            self::TYPE_OUT => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            default => 'default',
        };
    }
}
