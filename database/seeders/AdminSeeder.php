<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate: menjalankan seeder ulang TIDAK mereset password admin.
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@karirakademi.id')],
            [
                'name' => 'Administrator',
                'password' => env('ADMIN_PASSWORD', 'admin12345'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
