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
            $city = City::where('region_id', $region->id)
                ->where('slug', 'qorovulbozor') // masalan: Buxoro tumani
                ->first();
        }

        User::create([
            'name'      => 'Admin',
            'phone'     => '+998901234567',
            'role'      => 'admin',
            'email'     => 'admin@gmail.com',
            'password'  => Hash::make('11111'),
            'region_id' => $region?->id,
            'city_id'   => $city?->id,
        ]);
    }
}