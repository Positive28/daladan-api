<?php

namespace App\Services\Auth;

use App\Jobs\SendSmsJob;
use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class PhoneVerificationService
{
    public function start(string $phone): PhoneVerification
    {
        $registered = User::query()
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($registered) {
            throw new HttpResponseException(
                response()->errorJson('This phone number is already registered. Please login.', 422)
            );
        }

        $existing = PhoneVerification::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if ($existing?->isMaxResend()) {
            throw new HttpResponseException(
                response()->errorJson('Max resend limit reached. Try again later.', 429)
            );
        }

        if ($existing && ! $existing->canResend()) {
            throw new HttpResponseException(
                response()->errorJson('Please wait before requesting a new code.', 429)
            );
        }

        $code = (string) random_int(100000, 999999);

        PhoneVerification::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->delete();

        $record = PhoneVerification::create([
            'phone' => $phone,
            'code_hash' => bcrypt($code),
            'expires_at' => now()->addMinutes(3),
            'attempts' => 0,
            'resend_count' => $existing ? ($existing->resend_count + 1) : 0,
            'resend_available_at' => now()->addMinute(),
        ]);

        SendSmsJob::dispatch($phone, "Tasdiqlash kodi: {$code}. 3 daqiqa ichida amal qiladi.");

        return $record;
    }

    public function verify(string $phone, string $code): bool
    {
        $record = PhoneVerification::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $record) {
            throw new HttpResponseException(
                response()->errorJson('Verification record not found.', 404)
            );
        }

        if ($record->isExpired()) {
            throw new HttpResponseException(
                response()->errorJson('OTP code has expired.', 422)
            );
        }

        if ($record->isMaxAttempts()) {
            throw new HttpResponseException(
                response()->errorJson('Max attempts reached. Request a new code.', 429)
            );
        }

        $record->increment('attempts');

        if (! password_verify($code, $record->code_hash)) {
            return false;
        }

        $record->update(['verified_at' => now()]);

        return true;
    }

    public function hasVerified(string $phone): bool
    {
        return PhoneVerification::query()
            ->where('phone', $phone)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(10))
            ->exists();
    }
}
