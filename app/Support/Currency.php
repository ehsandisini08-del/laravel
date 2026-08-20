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

    public static function terbilang(float|int|string $amount): string
    {
        $number = abs((int) round((float) $amount));

        if ($number === 0) {
            return 'Nol Rupiah';
        }

        $units = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        $words = static function (int $n) use (&$words, $units): string {
            if ($n < 12) {
                return $units[$n];
            }

            if ($n < 20) {
                return $words($n - 10).' Belas';
            }

            if ($n < 100) {
                return $words(intdiv($n, 10)).' Puluh '.$words($n % 10);
            }

            if ($n < 200) {
                return 'Seratus '.$words($n - 100);
            }

            if ($n < 1000) {
                return $words(intdiv($n, 100)).' Ratus '.$words($n % 100);
            }

            if ($n < 2000) {
                return 'Seribu '.$words($n - 1000);
            }

            if ($n < 1_000_000) {
                return $words(intdiv($n, 1000)).' Ribu '.$words($n % 1000);
            }

            if ($n < 1_000_000_000) {
                return $words(intdiv($n, 1_000_000)).' Juta '.$words($n % 1_000_000);
            }

            if ($n < 1_000_000_000_000) {
                return $words(intdiv($n, 1_000_000_000)).' Miliar '.$words($n % 1_000_000_000);
            }

            return $words(intdiv($n, 1_000_000_000_000)).' Triliun '.$words($n % 1_000_000_000_000);
        };

        return trim(preg_replace('/\s+/', ' ', $words($number))).' Rupiah';
    }
}
