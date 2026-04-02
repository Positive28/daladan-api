<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class SubcategorySeeder extends Seeder
{
    public function run(): void
    {
        $byCategorySlug = [
            'meva' => [
                ['name' => 'Olma', 'slug' => 'olma'],
                ['name' => 'Uzum', 'slug' => 'uzum'],
                ['name' => 'Nok', 'slug' => 'nok'],
                ['name' => 'Shaftoli', 'slug' => 'shaftoli'],
                ['name' => 'O‘rik', 'slug' => 'orik'],
                ['name' => 'Anor', 'slug' => 'anor'],
                ['name' => 'Gilos', 'slug' => 'gilos'],
            ],
            'sabzavot' => [
                ['name' => 'Pomidor', 'slug' => 'pomidor'],
                ['name' => 'Bodring', 'slug' => 'bodring'],
                ['name' => 'Piyoz', 'slug' => 'piyoz'],
                ['name' => 'Kartoshka', 'slug' => 'kartoshka'],
                ['name' => 'Sabzi', 'slug' => 'sabzi'],
                ['name' => 'Qalampir', 'slug' => 'qalampir'],
                ['name' => 'Baqlajon', 'slug' => 'baqlajon'],
                ['name' => 'Karam', 'slug' => 'karam'],
            ],
            'don' => [
                ['name' => 'Bug‘doy', 'slug' => 'bugdoy'],
                ['name' => 'Arpa', 'slug' => 'arpa'],
                ['name' => 'Guruch', 'slug' => 'guruch'],
                ['name' => 'Makkajo‘xori', 'slug' => 'makkajoxori'],
                ['name' => 'Soya', 'slug' => 'soya'],
            ],
            'chorva-hayvonlari' => [
                ['name' => 'Qo‘ylar', 'slug' => 'qoylar'],
                ['name' => 'Echkilar', 'slug' => 'echkilar'],
                ['name' => 'Qoramol', 'slug' => 'qoramol'],
                ['name' => 'Otlar', 'slug' => 'otlar'],
                ['name' => 'Tuya', 'slug' => 'tuya'],
            ],
            'parranda' => [
                ['name' => 'Tovuqlar', 'slug' => 'tovuqlar'],
                ['name' => 'Xo‘rozlar', 'slug' => 'xorozlar'],
                ['name' => 'Kurka', 'slug' => 'kurka'],
                ['name' => 'O‘rdak', 'slug' => 'ordak'],
                ['name' => 'G‘oz', 'slug' => 'goz'],
                ['name' => 'Bedana', 'slug' => 'bedana'],
            ],
            'yem-ozuqa' => [
                ['name' => 'Omuxta yem', 'slug' => 'omuxta-yem'],
                ['name' => 'Somon', 'slug' => 'somon'],
                ['name' => 'Beda', 'slug' => 'beda'],
                ['name' => 'Kepak', 'slug' => 'kepak'],
                ['name' => 'Silos', 'slug' => 'silos'],
            ],
            'ogit-kimyoviylar' => [
                ['name' => 'Mineral o‘g‘itlar', 'slug' => 'mineral-ogitlar'],
                ['name' => 'Organik o‘g‘itlar', 'slug' => 'organik-ogitlar'],
                ['name' => 'Herbitsid', 'slug' => 'herbitsid'],
                ['name' => 'Insektitsid', 'slug' => 'insektitsid'],
                ['name' => 'Fungitsid', 'slug' => 'fungitsid'],
            ],
            'texnika-uskunalar' => [
                ['name' => 'Traktor', 'slug' => 'traktor'],
                ['name' => 'Kultivator', 'slug' => 'kultivator'],
                ['name' => 'Sug‘orish uskunalari', 'slug' => 'sugorish-uskunalari'],
                ['name' => 'Ekin sepish (seyalkalar)', 'slug' => 'seyalkalar'],
                ['name' => 'O‘rim-yig‘im uskunalari', 'slug' => 'orim-yigim-uskunalari'],
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
                $payload = [
                    'category_id' => $category->id,
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                ];

                Subcategory::updateOrCreate(
                    ['category_id' => $category->id, 'slug' => $item['slug']],
                    $payload
                );
            }
        }
    }
}

