<?php

namespace App\Console\Commands;

use App\Services\AdPromotionService;
use Illuminate\Console\Command;

/**
 * bootstrap/app.php da schedule: hourly — serverda cron bilan schedule:run ishlashi kerak.
 */
class ExpireAdPromotions extends Command
{
    protected $signature = 'ads:expire-promotions';

    protected $description = 'Muddati o\'tgan ad_promotions ni expired qilish va ads yorqinligini yangilash';

    public function handle(AdPromotionService $service): int
    {
        $n = $service->expireDuePromotions();

        if ($n > 0) {
            $this->info("Yopilgan promo buyurtmalari: {$n}");
        }

        return self::SUCCESS;
    }
}
