<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@kindecake.com'],
            [
                'name' => 'Kinde Cake Owner',
                'password' => 'password123',
                'role' => User::ROLE_OWNER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
