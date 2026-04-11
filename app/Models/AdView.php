<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ad_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
