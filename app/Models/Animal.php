<?php

namespace App\Models;

use App\Models\Concerns\HasAdDetail;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Animal extends Model
{
    use HasAdDetail, InteractsWithMedia;

    protected $fillable = [
        'ad_id',
        'title',
        'description',
        'price',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->acceptsMimeTypes(['image/jpeg', 'image/png'])->withResponsiveImages();
    }
}
