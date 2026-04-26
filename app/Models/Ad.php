<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Ad extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SOLD = 'sold';
    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'seller_id',
        'category_id',
        'subcategory_id',
        'region_id',
        'city_id',
        'title',
        'description',
        'district',
        'price',
        'quantity',
        'unit',
        'status',
        'is_top_sale',
        'is_boosted',
        'boost_starts_at',
        'boost_expires_at',
        'views_count',
        'expires_at',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_top_sale'      => 'boolean',
            'is_boosted'       => 'boolean',
            'quantity'         => 'decimal:2',
            'boost_starts_at'  => 'datetime',
            'boost_expires_at' => 'datetime',
            'expires_at'       => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function adPromotions()
    {
        return $this->hasMany(AdPromotion::class);
    }

    // -------------------------------------------------------------------------
    // Promo (top / boost) — ads jadvalida tez ishlatish; batafsil tarix ad_promotions da.
    // -------------------------------------------------------------------------

    /** Hozirgi vaqtda yorqinlik "ko'rinadigan" oynada ekanligi (tugash + boshlash). */
    public function highlightIsLive(?Carbon $at = null): bool
    {
        $at ??= now();

        if (!$this->boost_expires_at || $this->boost_expires_at->lte($at)) {
            return false;
        }

        if ($this->boost_starts_at !== null && $this->boost_starts_at->gt($at)) {
            return false;
        }

        return $this->is_top_sale || $this->is_boosted;
    }

    /** Oddiy tarif: ikkala tur o'chadi, muddat maydonlari null. */
    public function clearHighlight(): void
    {
        $this->forceFill([
            'is_top_sale' => false,
            'is_boosted' => false,
            'boost_starts_at' => null,
            'boost_expires_at' => null,
        ])->save();
    }

    /** Admin promo tasdiqidan keyin: plan turi bo'yicha bayroqlar + boost_starts_at / boost_expires_at. */
    public function applyHighlightFromPlan(PromotionPlan $plan, Carbon $startsAt, Carbon $expiresAt): void
    {
        $this->forceFill([
            'is_top_sale' => $plan->type === PromotionPlan::TYPE_TOP_SALE,
            'is_boosted' => $plan->type === PromotionPlan::TYPE_BOOST,
            'boost_starts_at' => $startsAt,
            'boost_expires_at' => $expiresAt,
        ])->save();
    }

    /** GET /public/ads: avval jonli boost, keyin top, qolganlari (keyin created_at). */
    public function scopeOrderByLiveHighlight(Builder $query): Builder
    {
        $t = now()->format('Y-m-d H:i:s');

        // PostgreSQL: boolean ustun bilan `= 1` solishtirish xato beradi (boolean = integer).
        // MySQL/SQLite: tinyint/0-1 uchun `= 1` ishlatiladi.
        $driver = $query->getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            return $query->orderByRaw(
                'CASE
                    WHEN is_boosted IS TRUE
                        AND (boost_starts_at IS NULL OR boost_starts_at <= ?)
                        AND boost_expires_at > ? THEN 0
                    WHEN is_top_sale IS TRUE
                        AND (boost_starts_at IS NULL OR boost_starts_at <= ?)
                        AND boost_expires_at > ? THEN 1
                    ELSE 2
                END',
                [$t, $t, $t, $t]
            );
        }

        return $query->orderByRaw(
            'CASE
                WHEN is_boosted = 1
                    AND (boost_starts_at IS NULL OR boost_starts_at <= ?)
                    AND boost_expires_at > ? THEN 0
                WHEN is_top_sale = 1
                    AND (boost_starts_at IS NULL OR boost_starts_at <= ?)
                    AND boost_expires_at > ? THEN 1
                ELSE 2
            END',
            [$t, $t, $t, $t]
        );
    }

    // -------------------------------------------------------------------------

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery');
    }

    public function getMediaListAttribute(): array
    {
        return $this->getMedia('gallery')->map(fn ($m) => [
            'id'   => $m->id,
            'url'  => url($m->getUrl()),
            'type' => $m->mime_type,
        ])->values()->toArray();
    }

    protected $appends = ['media_list', 'highlight_active'];

    /** Front uchun: highlightIsLive() ning qisqa nomi. */
    public function getHighlightActiveAttribute(): bool
    {
        return $this->highlightIsLive();
    }

    public function views()
    {
        return $this->hasMany(AdView::class);
    }
}
