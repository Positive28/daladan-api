<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = [
        'name_uz',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }
}
