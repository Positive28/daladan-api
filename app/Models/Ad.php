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
        'district',
        'title',
        'description',
        'price',
        'quantity',
        'quantity_description',
        'unit',
        'delivery_info',
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
            'price' => 'decimal:2',
            'quantity' => 'decimal:2',
            'is_top_sale' => 'boolean',
            'is_boosted' => 'boolean',
            'boost_expires_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

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

    /** E'lon manzili = sotuvchi (user) manzili */
    public function getRegionIdAttribute(): ?int
    {
        return $this->seller?->region_id;
    }

    public function getCityIdAttribute(): ?int
    {
        return $this->seller?->city_id;
    }

    public function getRegionAttribute(): ?Region
    {
        return $this->seller?->region;
    }

    public function getCityAttribute(): ?City
    {
        return $this->seller?->city;
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function adPromotions()
    {
        return $this->hasMany(AdPromotion::class);
    }

    public function animal()
    {
        return $this->hasOne(Animal::class);
    }

    public function poultry()
    {
        return $this->hasOne(Poultry::class);
    }

    public function grain()
    {
        return $this->hasOne(Grain::class);
    }

    public function fruit()
    {
        return $this->hasOne(Fruit::class);
    }

    public function forage()
    {
        return $this->hasOne(Forage::class);
    }

    public function vegetable()
    {
        return $this->hasOne(Vegetable::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery'); // rasm va videolar
    }

    /** API uchun: gallery (rasm/video) ro'yxati — to'liq URL */
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
