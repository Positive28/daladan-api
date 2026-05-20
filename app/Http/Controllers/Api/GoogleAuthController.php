<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use OpenApi\Annotations as OA;
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

    /**
     * redirect() — GET /auth/google/redirect
     * @OA\Get(
     *     path="/auth/google/redirect",
     *     tags={"Auth"},
     *     summary="Google OAuth URL olish",
     *     description="Frontend 'Google orqali kirish' tugmasi bosilganda chaqiriladi. Javobdagi data.url ni brauzerda oching (window.location.href). Google login dan keyin backend /auth/google/callback ga qaytadi va foydalanuvchini FRONTEND_URL/auth/callback?token=... ga yo'naltiradi.",
     *     @OA\Response(response=200, description="Google OAuth URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="ok"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="url", type="string", example="https://accounts.google.com/o/oauth2/auth?client_id=...&redirect_uri=...")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Google sozlamalari (.env) noto'g'ri")
     * )
     */
    private function _swaggerGoogleRedirect(): void {}

    /**
     * callback() — GET /auth/google/callback
     * @OA\Get(
     *     path="/auth/google/callback",
     *     tags={"Auth"},
     *     summary="Google OAuth callback (Google server chaqiradi)",
     *     description="Frontend to'g'ridan-to'g'ri chaqirmaydi — Google login dan keyin avtomatik redirect. Muvaffaqiyatli bo'lsa HTTP 302 bilan FRONTEND_URL/auth/callback?token=JWT ga yo'naltiradi. Yangi user bo'lsa bazada yaratiladi (google_id, email, status=active).",
     *     @OA\Parameter(name="code", in="query", required=false, description="Google authorization code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="state", in="query", required=false, description="OAuth state",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=302, description="Frontend ga redirect",
     *         @OA\Header(header="Location", description="FRONTEND_URL/auth/callback?token=...", @OA\Schema(type="string", example="http://localhost:5174/auth/callback?token=eyJ0eXAiOiJKV1QiLCJhbGc..."))
     *     ),
     *     @OA\Response(response=422, description="Google autentifikatsiya xatosi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Google autentifikatsiyasida xatolik yuz berdi.")
     *         )
     *     )
     * )
     */
    private function _swaggerGoogleCallback(): void {}
}
