<?php

namespace App\Models;

use App\Models\Concerns\HasAdDetail;
use Illuminate\Database\Eloquent\Model;

class Poultry extends Model
{
    use HasAdDetail;

    protected $fillable = [
        'ad_id',
        'title',
        'description',
        'poultry_type',
        'breed',
        'price_per_head',
    ];
}
