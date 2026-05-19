<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '+998901234567'],
            [
                'fname'             => 'Super',
                'lname'             => 'Admin',
                'role'              => User::ROLE_ADMIN,
                'status'            => User::STATUS_ACTIVE,
                'registration_type' => User::TYPE_PHONE,
                'phone_verified_at' => now(),
                'email'             => 'phpartisan.aaaa@gmail.com',
                'password'          => 'admin12345',
            ]
        );
    }
}