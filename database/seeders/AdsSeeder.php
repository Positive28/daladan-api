<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AdsSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::query()->whereNotNull('phone')->first();

        if (! $seller) {
            return;
        }

        $subcategories = Subcategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'category_id', 'name', 'slug']);

        if ($subcategories->isEmpty()) {
            return;
        }

        $units = ['kg', 't', 'sum', 'don'];

        for ($i = 1; $i <= 10; $i++) {
            $sub = $subcategories->get(($i - 1) % $subcategories->count());

            $isTop = $i % 5 === 0;
            $isBoosted = $i % 7 === 0;

            $now = now();

            Ad::updateOrCreate(
                [
                    'seller_id' => $seller->id,
                    'subcategory_id' => $sub->id,
                    'title' => $sub->name . ' e\'lon #' . $i,
                ],
                [
                    'seller_id' => $seller->id,
                    'category_id' => $sub->category_id,
                    'subcategory_id' => $sub->id,
                    'region_id' => $seller->region_id,
                    'city_id' => $seller->city_id,
                    'district' => null,
                    'description' => $sub->name . ' bo‘yicha sifatli mahsulot. E\'lon #' . $i,
                    'price' => (string) (1000 + ($i * 2500)),
                    'quantity' => (string) (5 + $i),
                    'quantity_description' => null,
                    'unit' => $units[($i - 1) % count($units)],
                    'delivery_info' => 'Yetkazib beriladi (taxminan 1-3 kun)',
                    'status' => 'active',
                    'is_top_sale' => $isTop,
                    'is_boosted' => $isBoosted,
                    'boost_expires_at' => $isBoosted ? $now->copy()->addDays(3) : null,
                    'views_count' => rand(0, 250),
                    'expires_at' => $now->copy()->addDays(14),
                ]
            );
        }
    }
}

