<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'region_id',
        'name_uz',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }
}
