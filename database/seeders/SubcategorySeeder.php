<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class SubcategorySeeder extends Seeder
{
    public function run(): void
    {
        $mevaCategory = Category::where('slug', 'meva')->first();
        $sabzavotCategory = Category::where('slug', 'sabzavot')->first();
        $chorvaCategory = Category::where('slug', 'chorva-hayvonlari')->first();

        $subcategories = [
            // Meva
            ...( $mevaCategory ? [
                [
                    'category_id' => $mevaCategory->id,
                    'name' => 'Olma',
                    'slug' => 'olma',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'category_id' => $mevaCategory->id,
                    'name' => 'Uzum',
                    'slug' => 'uzum',
                    'sort_order' => 2,
                    'is_active' => true,
                ],
            ] : []),

            // Sabzavot
            ...( $sabzavotCategory ? [
                [
                    'category_id' => $sabzavotCategory->id,
                    'name' => 'Pomidor',
                    'slug' => 'pomidor',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ] : []),

            // Chorva hayvonlari
            ...( $chorvaCategory ? [
                [
                    'category_id' => $chorvaCategory->id,
                    'name' => 'Qo‘ylar',
                    'slug' => 'qoylar',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'category_id' => $chorvaCategory->id,
                    'name' => 'Echkilar',
                    'slug' => 'echkilar',
                    'sort_order' => 2,
                    'is_active' => true,
                ],
                [
                    'category_id' => $chorvaCategory->id,
                    'name' => 'Mollar',
                    'slug' => 'mollar',
                    'sort_order' => 3,
                    'is_active' => true,
                ],
                [
                    'category_id' => $chorvaCategory->id,
                    'name' => 'Otlar',
                    'slug' => 'otlar',
                    'sort_order' => 4,
                    'is_active' => true,
                ],
                [
                    'category_id' => $chorvaCategory->id,
                    'name' => 'Xo‘rozlar',
                    'slug' => 'xorozlar',
                    'sort_order' => 5,
                    'is_active' => true,
                ],
            ] : []),
        ];

        foreach ($subcategories as $item) {
            Subcategory::updateOrCreate(
                [
                    'category_id' => $item['category_id'],
                    'slug' => $item['slug'],
                ],
                $item
            );
        }
    }
}

