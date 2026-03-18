<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Meva',
                'slug' => 'meva',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Sabzavot',
                'slug' => 'sabzavot',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Don',
                'slug' => 'don',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Chorva hayvonlari',
                'slug' => 'chorva-hayvonlari',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $item) {
            Category::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }
    }
}

