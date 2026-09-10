<?php

namespace Database\Seeders;

use App\Services\RoleService;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(RoleService::class)->ensureDefaults();
    }
}
