<?php

namespace App\Services\Auth;

use App\Models\PhoneVerification;
use App\Models\User;
use App\Services\Sms\EskizSmsService;
use Illuminate\Http\Exceptions\HttpResponseException;

class PhoneVerificationService
{
    public function __construct(
        private readonly EskizSmsService $eskiz
    ) {}

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

        $otpMessage = "daladan.uz saytiga ro'yxatdan o'tish uchun tasdiqlash kodi: {$code}";
        if (config('services.eskiz.otp_use_test_template')) {
            $override = config('services.eskiz.otp_test_template_body');
            $smsBody = (is_string($override) && trim($override) !== '')
                ? trim($override)
                : (config('services.eskiz.trial_sms_bodies')[config('services.eskiz.otp_trial_variant')]
                    ?? config('services.eskiz.trial_sms_bodies')['uz']);
        } else {
            $smsBody = $otpMessage;
        }

        if (config('services.eskiz.otp_use_test_template')) {
            logger()->notice('otp.eskiz_trialing_sms', [
                'phone' => $phone,
                'code' => $code,
            ]);
        }

        try {
            $this->eskiz->send($phone, $smsBody);
        } catch (\Throwable $e) {
            $record->delete();
            logger()->warning('otp.sms_dispatch_failed', [
                'phone' => $phone,
                'exception' => $e->getMessage(),
            ]);

            $message = 'SMS yuborilmadi. Keyinroq qayta urinib ko\'ring.';
            if (config('app.debug')) {
                $message .= ' ('.$e->getMessage().')';
            }

            throw new HttpResponseException(
                response()->errorJson($message, 503)
            );
        }

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
