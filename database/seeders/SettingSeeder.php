<?php

namespace Database\Seeders;

use App\Services\AppSettingsService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(AppSettingsService::class);

        foreach (config('app_settings', []) as $key => $value) {
            $settings->set($key, $value);
        }
    }
}
