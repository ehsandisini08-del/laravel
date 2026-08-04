<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Success = 'success';
    case Pending = 'pending';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Berhasil',
            self::Pending => 'Menunggu',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Success => 'success',
            self::Pending => 'warning',
            self::Failed => 'danger',
            self::Expired => 'default',
        };
    }
}
