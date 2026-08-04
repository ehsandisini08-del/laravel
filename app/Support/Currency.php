<?php

namespace App\Support;

use App\Models\Setting;

class Currency
{
    public static function symbol(): string
    {
        return Setting::get('currency_symbol', 'Rp') ?: 'Rp';
    }

    public static function format(float|int|string $amount): string
    {
        return self::symbol().' '.number_format((float) $amount, 0, ',', '.');
    }
}
