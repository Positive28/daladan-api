<?php

namespace App\Models;

use App\Models\Concerns\HasAdDetail;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Animal extends Model
{
    use HasAdDetail, InteractsWithMedia;

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $fillable = [
        'ad_id',
        'age_months',
        'weight_kg',
        'breed',
        'purpose',
        'health',
        'milk_per_day_l',
        'is_pregnant',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg'      => 'decimal:1',
            'milk_per_day_l' => 'decimal:1',
            'is_pregnant'    => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->acceptsMimeTypes(['image/jpeg', 'image/png'])->withResponsiveImages();
    }
}
