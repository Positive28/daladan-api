<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        Region::updateOrCreate(
            ['slug' => 'buxoro'],
            [
                'name_uz'    => 'Buxoro viloyati',
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );
    }
}