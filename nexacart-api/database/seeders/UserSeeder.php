<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@nexacart.test'],
            [
                'name' => 'NexaCart Admin',
                'password' => 'password123',
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'seller@nexacart.test'],
            [
                'name' => 'Demo Seller',
                'password' => 'password123',
                'role' => UserRole::Seller,
                'status' => UserStatus::Active,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@nexacart.test'],
            [
                'name' => 'Demo Customer',
                'password' => 'password123',
                'role' => UserRole::Customer,
                'status' => UserStatus::Active,
            ]
        );
    }
}