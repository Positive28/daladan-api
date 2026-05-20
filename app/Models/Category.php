<?php

namespace App\Models;

use App\Models\Concerns\HasIconMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use HasIconMedia, InteractsWithMedia {
        HasIconMedia::registerMediaCollections insteadof InteractsWithMedia;
    }

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function rootSubcategories()
    {
        return $this->hasMany(Subcategory::class)->whereNull('parent_id');
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    protected $appends = ['icon_url'];
}
