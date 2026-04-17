<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bitta e'lon + bitta foydalanuvchi + tanlangan plan bo'yicha buyurtma yozuvi.
 * Joriy promo ko'rinishi ads jadvalida (is_top_sale, is_boosted, boost_starts_at, boost_expires_at) sinxron saqlanadi.
 */
class AdPromotion extends Model
{
    /** To'lov kutilmoqda — admin PATCH .../ad-promotions/{id}/confirm gacha. */
    public const STATUS_PENDING = 'pending';

    /** Ixtiyoriy oraliq holat (kelajakda to'lov integratsiyasi bo'lsa). */
    public const STATUS_PAID = 'paid';

    /** Promo oynasi boshlangan; ads bilan sinxron. */
    public const STATUS_ACTIVE = 'active';

    /** expires_at o'tdi; scheduler yoki manual. */
    public const STATUS_EXPIRED = 'expired';

    /** Yangi tasdiq yoki bekor qilish. */
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'ad_id',
        'user_id',
        'promotion_plan_id',
        'amount_paid',
        'currency',
        'started_at',
        'expires_at',
        'status',
        'payment_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promotionPlan()
    {
        return $this->belongsTo(PromotionPlan::class);
    }
}
