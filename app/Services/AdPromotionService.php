<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdPromotion;
use App\Models\PromotionPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Promo (top / boost) buyurtma oqimi.
 *
 * Ketma-ketlik:
 * 1) Admin e'lonni active qiladi (AdminCheckController::approve).
 * 2) Sotuvchi promotion_plan tanlab pending buyurtma yaratadi (createPendingOrder).
 * 3) Click/Payme dan keyin admin confirm qiladi (confirmPending) — ad_promotions active + ads ustunlari sinxron.
 * 4) Scheduler expireDuePromotions — muddati o'tgach expired va ads yorqinligi tozalanadi.
 *
 * Manbalar: promotion_plans (tarif katalogi), ad_promotions (har bir buyurtma), ads (tez filter / ro'yxat tartibi).
 */
class AdPromotionService
{
    /**
     * @return array{0: Carbon, 1: Carbon} [boshlash, tugash] — boshlash: ertadan 00:00, tugash: +plan.duration_days.
     */
    public function windowFromPlan(PromotionPlan $plan): array
    {
        $tz = config('app.timezone');
        $start = Carbon::now($tz)->addDay()->startOfDay();
        $end = $start->copy()->addDays($plan->duration_days);

        return [$start, $end];
    }

    /**
     * Sotuvchi to'lovdan oldin: faqat active e'lon, bitta pending/active promo cheklovi.
     * started_at / expires_at — admin tasdiqgacha null (to'lov kelishini kutish).
     */
    public function createPendingOrder(Ad $ad, User $user, PromotionPlan $plan): AdPromotion
    {
        if ($ad->status !== Ad::STATUS_ACTIVE) {
            throw new \InvalidArgumentException('Promo faqat faol e\'longa buyurtma qilinadi.');
        }

        if ((int) $ad->seller_id !== (int) $user->id) {
            throw new \InvalidArgumentException('Bu e\'lon sizga tegishli emas.');
        }

        if (!$plan->is_active) {
            throw new \InvalidArgumentException('Bu tarif hozir mavjud emas.');
        }

        return DB::transaction(function () use ($ad, $user, $plan) {
            // Bir e'londa parallel: yana bitta kutilayotgan yoki allaqachon ishlayotgan promo bo'lmasin.
            $blocking = AdPromotion::query()
                ->where('ad_id', $ad->id)
                ->whereIn('status', [AdPromotion::STATUS_PENDING, AdPromotion::STATUS_ACTIVE])
                ->exists();

            if ($blocking) {
                throw new \InvalidArgumentException('Bu e\'londa allaqachon kutilayotgan yoki faol promo bor.');
            }

            // amount_paid: kutilayotgan to'lov summasi (promotion_plans.price bilan mos).
            return AdPromotion::create([
                'ad_id' => $ad->id,
                'user_id' => $user->id,
                'promotion_plan_id' => $plan->id,
                'amount_paid' => $plan->price,
                'currency' => $plan->currency,
                'started_at' => null,
                'expires_at' => null,
                'status' => AdPromotion::STATUS_PENDING,
                'payment_transaction_id' => null,
            ]);
        });
    }

    /**
     * Admin tasdiq: boshqa pending/active yozuvlarni bekor qiladi, shu buyurtmani active qiladi,
     * vaqt oynasini hisoblab ads (is_top_sale / is_boosted / boost_*_at) ga yozadi.
     */
    public function confirmPending(AdPromotion $promotion, ?string $paymentTransactionId = null): void
    {
        DB::transaction(function () use ($promotion, $paymentTransactionId) {
            $promotion->refresh();

            if ($promotion->status !== AdPromotion::STATUS_PENDING) {
                throw new \InvalidArgumentException('Faqat kutilayotgan (pending) buyurtma tasdiqlanadi.');
            }

            $ad = $promotion->ad()->lockForUpdate()->first();
            $plan = $promotion->promotionPlan()->lockForUpdate()->first();

            if (!$ad || !$plan) {
                throw new \InvalidArgumentException('E\'lon yoki tarif topilmadi.');
            }

            if ($ad->status !== Ad::STATUS_ACTIVE) {
                throw new \InvalidArgumentException('E\'lon faol emas.');
            }

            // Shu e'londagi eski buyurtmalar (boshqa pending yoki eski active) — yangi tasdiq bilan bekor.
            AdPromotion::query()
                ->where('ad_id', $ad->id)
                ->where('id', '!=', $promotion->id)
                ->whereIn('status', [AdPromotion::STATUS_PENDING, AdPromotion::STATUS_ACTIVE])
                ->update([
                    'status' => AdPromotion::STATUS_CANCELLED,
                    'started_at' => null,
                    'expires_at' => null,
                ]);

            // Ertadan 00:00 dan boshlab, plandagi duration_days qadar.
            [$start, $end] = $this->windowFromPlan($plan);

            $promotion->update([
                'status' => AdPromotion::STATUS_ACTIVE,
                'started_at' => $start,
                'expires_at' => $end,
                'payment_transaction_id' => $paymentTransactionId,
            ]);

            // Ro'yxat / filter uchun ads jadvalidagi "joriy promo" maydonlari.
            $ad->applyHighlightFromPlan($plan, $start, $end);
        });
    }

    /**
     * Cron (schedule) chaqiradi: active promo muddati tugagan bo'lsa expired + ads tozalash.
     */
    public function expireDuePromotions(): int
    {
        $ids = AdPromotion::query()
            ->where('status', AdPromotion::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        $n = 0;

        DB::transaction(function () use ($ids, &$n) {
            foreach ($ids as $id) {
                $promotion = AdPromotion::query()->whereKey($id)->lockForUpdate()->first();
                if (!$promotion || $promotion->status !== AdPromotion::STATUS_ACTIVE) {
                    continue;
                }
                if ($promotion->expires_at === null || $promotion->expires_at->gt(now())) {
                    continue;
                }

                $promotion->update(['status' => AdPromotion::STATUS_EXPIRED]);
                $ad = $promotion->ad;

                // Shu e'londa boshqa active promo qolmagan bo'lsa — ads yorqinligini oddiyga qaytarish.
                $stillActive = AdPromotion::query()
                    ->where('ad_id', $ad->id)
                    ->where('status', AdPromotion::STATUS_ACTIVE)
                    ->exists();

                if (!$stillActive) {
                    $ad->clearHighlight();
                }

                $n++;
            }
        });

        return $n;
    }
}
