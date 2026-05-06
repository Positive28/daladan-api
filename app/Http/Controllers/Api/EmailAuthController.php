<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use OpenApi\Annotations as OA;

class EmailAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fname' => ['nullable', 'string', 'max:255'],
            'lname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = User::create([
            'fname' => $validated['fname'] ?? null,
            'lname' => $validated['lname'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_USER,
            'status' => User::STATUS_PENDING,
            'registration_type' => User::TYPE_EMAIL,
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your email.',
        ], 201);
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse|JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return response()->errorJson('User not found.', 404);
        }

        if (! hash_equals((string) $hash, sha1((string) $user->getEmailForVerification()))) {
            return response()->errorJson('Invalid verification link.', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $user->forceFill(['status' => User::STATUS_ACTIVE])->save();

        $token = auth('api')->login($user);
        $frontendUrl = config('app.frontend_url') ?: config('app.url');

        return redirect(rtrim((string) $frontendUrl, '/') . '/auth/callback?token=' . urlencode((string) $token));
    }

    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user) {
            return response()->errorJson('User not found.', 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email resent.',
        ]);
    }

    // =========================================================================
    // Swagger / OpenAPI
    // =========================================================================

    /**
     * register() — POST /auth/email/register
     * @OA\Post(
     *     path="/auth/email/register",
     *     tags={"Auth"},
     *     summary="Email orqali ro'yxatdan o'tish",
     *     description="status=pending; email tasdiqlanguncha POST /login bloklanadi. Keyin GET /auth/email/verify/{id}/{hash} (signed).",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"email","password","password_confirmation"},
     *             @OA\Property(property="fname", type="string", nullable=true, example="Ali"),
     *             @OA\Property(property="lname", type="string", nullable=true, example="Valiyev"),
     *             @OA\Property(property="email", type="string", format="email", example="ali@mail.com"),
     *             @OA\Property(property="password", type="string", format="password", example="parol123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="parol123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tasdiq xati yuborildi"),
     *     @OA\Response(response=422, description="Validatsiya")
     * )
     */
    private function _swaggerEmailRegister(): void {}

    /**
     * resend() — POST /auth/email/resend
     * @OA\Post(
     *     path="/auth/email/resend",
     *     tags={"Auth"},
     *     summary="Tasdiq xatini qayta yuborish",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="ali@mail.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description="Foydalanuvchi topilmadi")
     * )
     */
    private function _swaggerEmailResend(): void {}

    /**
     * verify() — GET /auth/email/verify/{id}/{hash}
     * @OA\Get(
     *     path="/auth/email/verify/{id}/{hash}",
     *     tags={"Auth"},
     *     summary="Emailni tasdiqlash (signed URL, pochta orqali)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="hash", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=302, description="Frontend /auth/callback ga token bilan redirect"),
     *     @OA\Response(response=403, description="Noto'g'ri havola"),
     *     @OA\Response(response=404, description="User topilmadi")
     * )
     */
    private function _swaggerEmailVerify(): void {}
}
