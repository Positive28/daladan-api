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

        return $this->sendOtp($phone, PhoneVerification::PURPOSE_REGISTER);
    }

    public function forgot(string $phone): PhoneVerification
    {
        $user = User::query()
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->first();

        if (! $user) {
            throw new HttpResponseException(
                response()->errorJson('No account found with this phone number.', 404)
            );
        }

        return $this->sendOtp($phone, PhoneVerification::PURPOSE_RESET);
    }

    public function verify(string $phone, string $code, string $purpose = PhoneVerification::PURPOSE_REGISTER): bool
    {
        $record = PhoneVerification::query()
            ->where('phone', $phone)
            ->where('otp_purpose', $purpose)
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

    public function resetPassword(string $phone, string $password): void
    {
        $verified = PhoneVerification::query()
            ->where('phone', $phone)
            ->where('otp_purpose', PhoneVerification::PURPOSE_RESET)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(10))
            ->exists();

        if (! $verified) {
            throw new HttpResponseException(
                response()->errorJson('Phone number is not verified for password reset.', 403)
            );
        }

        $user = User::query()
            ->where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->first();

        if (! $user) {
            throw new HttpResponseException(
                response()->errorJson('User not found.', 404)
            );
        }

        $user->update(['password' => $password]);

        PhoneVerification::query()
            ->where('phone', $phone)
            ->where('otp_purpose', PhoneVerification::PURPOSE_RESET)
            ->delete();
    }

    public function hasVerified(string $phone, string $purpose = PhoneVerification::PURPOSE_REGISTER): bool
    {
        return PhoneVerification::query()
            ->where('phone', $phone)
            ->where('otp_purpose', $purpose)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(10))
            ->exists();
    }

    private function sendOtp(string $phone, string $purpose): PhoneVerification
    {
        $existing = PhoneVerification::query()
            ->where('phone', $phone)
            ->where('otp_purpose', $purpose)
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
            ->where('otp_purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $record = PhoneVerification::create([
            'phone'               => $phone,
            'otp_purpose'         => $purpose,
            'code_hash'           => bcrypt($code),
            'expires_at'          => now()->addMinutes(3),
            'attempts'            => 0,
            'resend_count'        => $existing ? ($existing->resend_count + 1) : 0,
            'resend_available_at' => now()->addMinute(),
        ]);

        $smsBody = $this->buildSmsBody($phone, $code, $purpose);

        try {
            $this->eskiz->send($phone, $smsBody);
        } catch (\Throwable $e) {
            $record->delete();
            logger()->warning('otp.sms_dispatch_failed', [
                'phone'     => $phone,
                'purpose'   => $purpose,
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

    private function buildSmsBody(string $phone, string $code, string $purpose): string
    {
        if (config('services.eskiz.otp_use_test_template')) {
            logger()->notice('otp.eskiz_trialing_sms', ['phone' => $phone, 'code' => $code, 'purpose' => $purpose]);
            $override = config('services.eskiz.otp_test_template_body');
            return (is_string($override) && trim($override) !== '')
                ? trim($override)
                : (config('services.eskiz.trial_sms_bodies')[config('services.eskiz.otp_trial_variant')]
                    ?? config('services.eskiz.trial_sms_bodies')['uz']);
        }

        return match ($purpose) {
            PhoneVerification::PURPOSE_RESET => "daladan.uz saytiga kirish uchun parolni tiklash kodi: {$code}",
            default                          => "daladan.uz saytiga ro'yxatdan o'tish uchun tasdiqlash kodi: {$code}",
        };
    }
}
