<?php

namespace App\Support;

use App\Models\Setting;

class SettingSupport
{
    public static function perPage(): int
    {
        $value = (int) Setting::get('pagination', '15');

        return $value >= 5 && $value <= 100 ? $value : 15;
    }
}
