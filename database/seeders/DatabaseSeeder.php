<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            RegionSeeder::class,
            CitySeeder::class,
            // Category / Subcategory — adminka orqali qo'lda; kerak bo'lsa: php artisan db:seed --class=CategorySeeder
            PromotionPlanSeeder::class,
            UserSeeder::class,
        ]);
    }
}
