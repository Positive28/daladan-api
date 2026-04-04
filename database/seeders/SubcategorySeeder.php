<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class SubcategorySeeder extends Seeder
{
    public function run(): void
    {
        // Kalit sluglar CategorySeeder dagi sluglar bilan AYNAN mos bo'lishi kerak
        $byCategorySlug = [
            'fruit' => [
                ['name' => 'Olma',     'slug' => 'apple'],
                ['name' => 'Uzum',     'slug' => 'grape'],
                ['name' => 'Nok',      'slug' => 'pear'],
                ['name' => 'Shaftoli', 'slug' => 'peach'],
                ['name' => "O'rik",    'slug' => 'apricot'],
                ['name' => 'Anor',     'slug' => 'pomegranate'],
                ['name' => 'Gilos',    'slug' => 'cherry'],
            ],
            'vegetable' => [
                ['name' => 'Pomidor',   'slug' => 'tomato'],
                ['name' => 'Bodring',   'slug' => 'cucumber'],
                ['name' => 'Piyoz',     'slug' => 'onion'],
                ['name' => 'Kartoshka', 'slug' => 'potato'],
                ['name' => 'Sabzi',     'slug' => 'carrot'],
                ['name' => 'Qalampir',  'slug' => 'pepper'],
                ['name' => 'Baqlajon',  'slug' => 'eggplant'],
                ['name' => 'Karam',     'slug' => 'cabbage'],
            ],
            'grain' => [
                ['name' => "Bug'doy",      'slug' => 'wheat'],
                ['name' => 'Arpa',         'slug' => 'barley'],
                ['name' => 'Guruch',       'slug' => 'rice'],
                ['name' => "Makkajo'xori", 'slug' => 'corn'],
                ['name' => 'Soya',         'slug' => 'soybean'],
            ],
            'animal' => [
                ['name' => "Qo'ylar",  'slug' => 'sheep'],
                ['name' => 'Echkilar', 'slug' => 'goat'],
                ['name' => 'Qoramol',  'slug' => 'cattle'],
                ['name' => 'Otlar',    'slug' => 'horse'],
                ['name' => 'Tuya',     'slug' => 'camel'],
            ],
            'poultry' => [
                ['name' => 'Tovuqlar',   'slug' => 'chicken'],
                ['name' => "Xo'rozlar",  'slug' => 'rooster'],
                ['name' => 'Kurka',      'slug' => 'turkey'],
                ['name' => "O'rdak",     'slug' => 'duck'],
                ['name' => "G'oz",       'slug' => 'goose'],
                ['name' => 'Bedana',     'slug' => 'quail'],
            ],
            'forage' => [
                ['name' => 'Omuxta yem', 'slug' => 'compound_feed'],
                ['name' => 'Somon',      'slug' => 'straw'],
                ['name' => 'Beda',       'slug' => 'alfalfa'],
                ['name' => 'Kepak',      'slug' => 'bran'],
                ['name' => 'Silos',      'slug' => 'silage'],
            ],
            'fertilizer' => [
                ['name' => "Mineral o'g'itlar", 'slug' => 'mineral_fertilizer'],
                ['name' => "Organik o'g'itlar", 'slug' => 'organic_fertilizer'],
                ['name' => 'Herbitsid',         'slug' => 'herbicide'],
                ['name' => 'Insektitsid',       'slug' => 'insecticide'],
                ['name' => 'Fungitsid',         'slug' => 'fungicide'],
            ],
            'agricultural-equipment' => [
                ['name' => 'Traktor',                   'slug' => 'tractor'],
                ['name' => 'Kultivator',                'slug' => 'cultivator'],
                ['name' => "Sug'orish uskunalari",      'slug' => 'irrigation_equipment'],
                ['name' => 'Ekin sepish (seyalkalar)',  'slug' => 'seeder'],
                ['name' => "O'rim-yig'im uskunalari",   'slug' => 'harvesting_equipment'],
            ],
        ];

        $categories = Category::query()
            ->whereIn('slug', array_keys($byCategorySlug))
            ->get(['id', 'slug'])
            ->keyBy('slug');

        foreach ($byCategorySlug as $categorySlug => $items) {
            $category = $categories->get($categorySlug);
            if (! $category) {
                continue;
            }

            foreach (array_values($items) as $idx => $item) {
                Subcategory::updateOrCreate(
                    ['category_id' => $category->id, 'slug' => $item['slug']],
                    [
                        'category_id' => $category->id,
                        'name'        => $item['name'],
                        'slug'        => $item['slug'],
                        'sort_order'  => $idx + 1,
                        'is_active'   => true,
                    ]
                );
            }
        }
    }
}
