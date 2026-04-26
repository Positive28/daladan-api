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
            'fname'     => 'Super',
            'lname'     => 'Admin',
            'role'      => User::ROLE_ADMIN,
            'email'     => 'admin@gmail.com',
            // Model cast "hashed" bo'lgani uchun bu yerda plain password beramiz.
            'password'  => '11111',
            ]
        );
    }
}