<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // Buxoro viloyatini slug bo'yicha topamiz
        $region = Region::where('slug', 'buxoro')->first();

        if (! $region) {
            // Agar avval RegionSeeder ishlamagan bo'lsa, hech narsa qilmaymiz
            return;
        }

        $cities = [
            ['name_uz' => 'Buxoro tumani',      'slug' => 'buxoro',        'sort_order' => 1],
            ['name_uz' => 'Vobkent tumani',     'slug' => 'vobkent',       'sort_order' => 2],
            ['name_uz' => 'Jondor tumani',      'slug' => 'jondor',        'sort_order' => 3],
            ['name_uz' => 'Kogon tumani',       'slug' => 'kogon',         'sort_order' => 4],
            ['name_uz' => 'Olot tumani',        'slug' => 'olot',          'sort_order' => 5],
            ['name_uz' => 'Peshkoʻ tumani',     'slug' => 'peshku',        'sort_order' => 6],
            ['name_uz' => 'Romitan tumani',     'slug' => 'romitan',       'sort_order' => 7],
            ['name_uz' => 'Shofirkon tumani',   'slug' => 'shofirkon',     'sort_order' => 8],
            ['name_uz' => 'Qorovulbozor tumani','slug' => 'qorovulbozor',  'sort_order' => 9],
            ['name_uz' => 'Qorakoʻl tumani',    'slug' => 'qorakol',       'sort_order' => 10],
            ['name_uz' => 'Gʻijduvon tumani',   'slug' => 'gijduvon',      'sort_order' => 11],
        ];

        foreach ($cities as $item) {
            City::updateOrCreate(
                [
                    'region_id' => $region->id,
                    'slug'      => $item['slug'],
                ],
                [
                    'name_uz'    => $item['name_uz'],
                    'sort_order' => $item['sort_order'],
                    'is_active'  => true,
                ]
            );
        }
    }
}