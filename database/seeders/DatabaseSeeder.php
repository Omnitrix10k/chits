<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = (string) env('ADMIN_EMAIL', 'admin@example.com');
        $adminMobile = (string) env('ADMIN_MOBILE', '+15555550100');
        $adminPassword = (string) env('ADMIN_PASSWORD', 'password');

        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'mobile_number' => $adminMobile,
                'primary_phone' => $adminMobile,
                'address' => null,
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
                'password' => Hash::make($adminPassword),
            ]
        );
    }
}
