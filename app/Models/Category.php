<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use InteractsWithMedia;

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

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('image');

        return $media ? url($media->getUrl()) : null;
    }

    protected $appends = ['image_url'];
}
