<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Region;
use App\Models\City;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buxoro viloyatini topamiz
        $region = Region::where('slug', 'buxoro')->first();

        // 2. Buxoro tumani (yoki istalgan boshqa tuman) ni topamiz
        $city = null;
        if ($region) {
            // CitySeeder'da slug nomdan avtomatik yasalgani uchun
            // "qorovulbozor" ko'rinishi bilan filtr qilamiz.
            $city = City::where('region_id', $region->id)
                ->where('slug', 'like', '%qorovulbozor%')
                ->first();
        }

        User::updateOrCreate(
            ['phone' => '+998901234567'],
            [
            'fname'     => 'Super',
            'lname'     => 'Admin',
            'role'      => User::ROLE_ADMIN,
            'email'     => 'admin@gmail.com',
            // Model cast "hashed" bo'lgani uchun bu yerda plain password beramiz.
            'password'  => '11111',
            'region_id' => $region?->id,
            'city_id'   => $city?->id,
            ]
        );
    }
}