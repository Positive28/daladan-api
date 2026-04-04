<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Ad extends Model implements HasMedia
{
    use InteractsWithMedia;

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
        'boost_expires_at',
        'views_count',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_top_sale'      => 'boolean',
            'is_boosted'       => 'boolean',
            'quantity'         => 'decimal:2',
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

    public function getRegionAttribute(): ?Region
    {
        return $this->seller?->region;
    }

    public function getCityAttribute(): ?City
    {
        return $this->seller?->city;
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

    protected $appends = ['media_list'];
}
