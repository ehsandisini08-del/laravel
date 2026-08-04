<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(SettingService::class);

        foreach ($service->sections() as $group => $section) {
            foreach ($section['fields'] as $key => $field) {
                Setting::set($key, (string) $field['default'], $group);
            }
        }
    }
}
