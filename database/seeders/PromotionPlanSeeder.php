<?php

namespace Database\Seeders;

use App\Models\PromotionPlan;
use Illuminate\Database\Seeder;

class PromotionPlanSeeder extends Seeder
{
    /**
     * promotion_plans jadvali: GET /resources/promotion-plans va buyurtma (promotion_plan_id).
     * 7 kun, 15 kun, 1 oy (30 kun), 2 oy (60 kun) × top_sale va boost.
     * 7 kun narxlari: Top 9000, Boost 5000 UZS; qolganlar — keyin o'zgartiring.
     */
    public function run(): void
    {
        $currency = 'UZS';

        $durations = [
            ['days' => 7, 'label' => '7 kun', 'slug_suffix' => '7d', 'sort' => 1],
            ['days' => 15, 'label' => '15 kun', 'slug_suffix' => '15d', 'sort' => 2],
            ['days' => 30, 'label' => '1 oy', 'slug_suffix' => '1m', 'sort' => 3],
            ['days' => 60, 'label' => '2 oy', 'slug_suffix' => '2m', 'sort' => 4],
        ];

        $pricesTop = [9_000, 49_000, 129_000, 219_000];
        $pricesBoost = [5_000, 69_000, 169_000, 289_000];

        foreach ($durations as $i => $row) {
            PromotionPlan::query()->updateOrCreate(
                ['slug' => 'top-sale-' . $row['slug_suffix']],
                [
                    'name' => 'Top sotuv — ' . $row['label'],
                    'description' => 'Top sotuv yorlig‘i, ' . $row['label'] . ' muddat.',
                    'price' => $pricesTop[$i],
                    'currency' => $currency,
                    'duration_days' => $row['days'],
                    'type' => PromotionPlan::TYPE_TOP_SALE,
                    'is_active' => true,
                    'sort_order' => $row['sort'],
                ]
            );
        }

        foreach ($durations as $i => $row) {
            PromotionPlan::query()->updateOrCreate(
                ['slug' => 'boost-' . $row['slug_suffix']],
                [
                    'name' => 'Boost — ' . $row['label'],
                    'description' => 'Boost ko‘rinishi, ' . $row['label'] . ' muddat.',
                    'price' => $pricesBoost[$i],
                    'currency' => $currency,
                    'duration_days' => $row['days'],
                    'type' => PromotionPlan::TYPE_BOOST,
                    'is_active' => true,
                    'sort_order' => 10 + $row['sort'],
                ]
            );
        }
    }
}
