<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Meva',                       'slug' => 'fruit',                'sort_order' => 1, 'is_active' => true],
            ['name' => 'Sabzavot',                   'slug' => 'vegetable',            'sort_order' => 2, 'is_active' => true],
            ['name' => 'Don',                        'slug' => 'grain',                'sort_order' => 3, 'is_active' => true],
            ['name' => 'Chorva',                     'slug' => 'animal',               'sort_order' => 4, 'is_active' => true],
            ['name' => 'Parranda',                   'slug' => 'poultry',              'sort_order' => 5, 'is_active' => true],
            ['name' => 'Yem va ozuqa',               'slug' => 'forage',               'sort_order' => 6, 'is_active' => true],
            ['name' => "O'g'it va kimyoviylar",      'slug' => 'fertilizer',           'sort_order' => 7, 'is_active' => true],
            ['name' => "Qishloq xo'jaligi jihozlari", 'slug' => 'agricultural_equipment', 'sort_order' => 8, 'is_active' => true],
        ];

        foreach ($categories as $item) {
            Category::updateOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
