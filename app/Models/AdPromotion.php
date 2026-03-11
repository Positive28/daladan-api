<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPromotion extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
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
