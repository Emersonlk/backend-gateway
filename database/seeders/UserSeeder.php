<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => UserRole::ADMIN,
        ]);

        User::query()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
            'role' => UserRole::MANAGER,
        ]);

        User::query()->create([
            'name' => 'Finance',
            'email' => 'finance@example.com',
            'password' => 'password',
            'role' => UserRole::FINANCE,
        ]);

        User::query()->create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password',
            'role' => UserRole::USER,
        ]);
    }
}
