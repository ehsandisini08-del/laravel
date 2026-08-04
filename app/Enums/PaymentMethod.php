<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case VaBca = 'va_bca';
    case VaBri = 'va_bri';
    case VaMandiri = 'va_mandiri';
    case VaBni = 'va_bni';
    case VaOther = 'va_other';
    case Qris = 'qris';
    case Ewallet = 'ewallet';
    case TransferBank = 'transfer_bank';
    case Gateway = 'gateway';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::VaBca => 'Virtual Account BCA',
            self::VaBri => 'Virtual Account BRI',
            self::VaMandiri => 'Virtual Account Mandiri',
            self::VaBni => 'Virtual Account BNI',
            self::VaOther => 'Virtual Account Lainnya',
            self::Qris => 'QRIS',
            self::Ewallet => 'E-Wallet',
            self::TransferBank => 'Transfer Bank',
            self::Gateway => 'Payment Gateway',
            self::Other => 'Lainnya',
        };
    }
}
