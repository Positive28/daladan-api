<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneVerification extends Model
{
    protected $fillable = [
        'phone',
        'code_hash',
        'expires_at',
        'attempts',
        'resend_count',
        'resend_available_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isMaxAttempts(): bool
    {
        return $this->attempts >= 3;
    }

    public function canResend(): bool
    {
        return $this->resend_available_at === null || $this->resend_available_at->isPast();
    }

    public function isMaxResend(): bool
    {
        return $this->resend_count >= 5;
    }
}
