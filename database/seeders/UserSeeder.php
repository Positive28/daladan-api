<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '+998882383808'],
            [
            'fname'     => 'Super',
            'lname'     => 'Admin',
            'role'      => User::ROLE_ADMIN,
            'email'     => 'phpartisan.aaaa@gmail.com',
            // Model cast "hashed" bo'lgani uchun bu yerda plain password beramiz.
            'password'  => 'admin12345',
            ]
        );
    }
}