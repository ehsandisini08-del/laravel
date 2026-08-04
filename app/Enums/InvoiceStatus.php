<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Unpaid = 'unpaid';
    case Overdue = 'overdue';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Unpaid => 'Belum Bayar',
            self::Overdue => 'Telat Bayar',
            self::Paid => 'Sudah Bayar',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'default',
            self::Unpaid => 'danger',
            self::Overdue => 'warning',
            self::Paid => 'success',
            self::Cancelled => 'default',
        };
    }
}
