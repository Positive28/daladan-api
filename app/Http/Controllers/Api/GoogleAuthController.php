<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): JsonResponse
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'success' => true,
            'message' => 'ok',
            'data' => ['url' => $url],
        ]);
    }

    public function callback(): RedirectResponse|JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable) {
            return response()->errorJson('Google autentifikatsiyasida xatolik yuz berdi.', 422);
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if (! $email || ! $googleId) {
            return response()->errorJson('Google email yoki id qaytmadi.', 422);
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        $fullName = trim((string) $googleUser->getName());
        $parts = preg_split('/\s+/', $fullName, 2) ?: [];
        $fname = $parts[0] ?? null;
        $lname = $parts[1] ?? null;

        if (! $user) {
            $user = User::create([
                'fname' => $fname,
                'lname' => $lname,
                'email' => $email,
                'google_id' => $googleId,
                'role' => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
                'registration_type' => User::TYPE_EMAIL,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->fill([
                'google_id' => $user->google_id ?: $googleId,
                'email_verified_at' => $user->email_verified_at ?: now(),
                'status' => User::STATUS_ACTIVE,
            ])->save();
        }

        $token = auth('api')->login($user);
        $frontendUrl = (string) (config('app.frontend_url') ?: config('app.url'));

        return redirect(rtrim($frontendUrl, '/') . '/auth/callback?token=' . urlencode((string) $token));
    }
}
