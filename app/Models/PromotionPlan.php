<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionPlan extends Model
{
    public const TYPE_TOP_SALE = 'top_sale';
    public const TYPE_BOOST = 'boost';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'duration_days',
        'type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function adPromotions()
    {
        return $this->hasMany(AdPromotion::class);
    }
}
