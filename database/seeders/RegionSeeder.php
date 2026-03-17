<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['slug' => 'andijon',   'name_uz' => 'Andijon viloyati'],
            ['slug' => 'buxoro',    'name_uz' => 'Buxoro viloyati'],
            ['slug' => 'fargona',   'name_uz' => 'Farg‘ona viloyati'],
            ['slug' => 'jizzax',    'name_uz' => 'Jizzax viloyati'],
            ['slug' => 'namangan',  'name_uz' => 'Namangan viloyati'],
            ['slug' => 'navoiy',    'name_uz' => 'Navoiy viloyati'],
            ['slug' => 'qashqadaryo','name_uz' => 'Qashqadaryo viloyati'],
            ['slug' => 'qoraqalpogiston','name_uz' => 'Qoraqalpog‘iston Respublikasi'],
            ['slug' => 'samarqand', 'name_uz' => 'Samarqand viloyati'],
            ['slug' => 'sirdaryo',  'name_uz' => 'Sirdaryo viloyati'],
            ['slug' => 'surxondaryo','name_uz' => 'Surxondaryo viloyati'],
            ['slug' => 'toshkent',  'name_uz' => 'Toshkent viloyati'],
            ['slug' => 'toshkent-shahar','name_uz' => 'Toshkent shahri'],
            ['slug' => 'xorazm',    'name_uz' => 'Xorazm viloyati'],
        ];

        $sort = 1;
        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['slug' => $region['slug']],
                [
                    'name_uz'    => $region['name_uz'],
                    'sort_order' => $sort++,
                    'is_active'  => true,
                ]
            );
        }
    }
}