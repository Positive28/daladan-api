<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneVerification;
use App\Models\User;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use OpenApi\Annotations as OA;

class PhoneAuthController extends Controller
{
    public function __construct(
        private readonly PhoneVerificationService $verificationService
    ) {}

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/'],
        ], [
            'phone.regex' => 'Phone number must be a valid Uzbek number (+998XXXXXXXXX).',
        ]);

        $this->verificationService->start($validated['phone']);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
            'data' => ['phone' => $validated['phone']],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/'],
            'code' => ['required', 'digits:6'],
        ]);

        $verified = $this->verificationService->verify($validated['phone'], $validated['code']);
        if (! $verified) {
            return response()->errorJson('Invalid OTP code. Please try again.', 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phone verified. Proceed to complete registration.',
            'data' => ['phone' => $validated['phone']],
        ]);
    }

    public function forgot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/'],
        ], [
            'phone.regex' => 'Phone number must be a valid Uzbek number (+998XXXXXXXXX).',
        ]);

        $this->verificationService->forgot($validated['phone']);

        return response()->json([
            'success' => true,
            'message' => 'Password reset OTP sent successfully.',
            'data'    => ['phone' => $validated['phone']],
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone'                 => ['required', 'string', 'regex:/^\+998\d{9}$/'],
            'code'                  => ['required', 'digits:6'],
            'password'              => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(6)],
        ]);

        $verified = $this->verificationService->verify(
            $validated['phone'],
            $validated['code'],
            \App\Models\PhoneVerification::PURPOSE_RESET
        );

        if (! $verified) {
            return response()->errorJson('Invalid OTP code. Please try again.', 422);
        }

        $this->verificationService->resetPassword($validated['phone'], $validated['password']);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. Please login.',
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/', 'unique:users,phone'],
            'fname' => ['nullable', 'string', 'max:255'],
            'lname' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        if (! $this->verificationService->hasVerified($validated['phone'])) {
            return response()->errorJson('Phone number is not verified. Please complete verification first.', 403);
        }

        $user = User::create([
            'phone' => $validated['phone'],
            'fname' => $validated['fname'] ?? null,
            'lname' => $validated['lname'] ?? null,
            'password' => $validated['password'],
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
            'registration_type' => User::TYPE_PHONE,
            'phone_verified_at' => now(),
        ]);

        PhoneVerification::query()
            ->where('phone', $validated['phone'])
            ->delete();

        $token = auth('api')->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => $user,
            ],
        ], 201);
    }

    // =========================================================================
    // Swagger / OpenAPI
    // =========================================================================

    /**
     * start() — POST /auth/phone/start
     * @OA\Post(
     *     path="/auth/phone/start",
     *     tags={"Auth"},
     *     summary="Telefon uchun OTP SMS yuborish",
     *     description="Berilgan O'zbekiston telefon raqamiga 6 xonali OTP kodni SMS orqali yuboradi. Kod 3 daqiqa amal qiladi.",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+998901234567", description="O'zbekiston raqami: +998XXXXXXXXX")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP yuborildi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP sent successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="phone", type="string", example="+998901234567")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validatsiya xatosi (noto'g'ri raqam yoki allaqachon ro'yxatdan o'tgan)"),
     *     @OA\Response(response=429, description="Ko'p urinish — kutish kerak"),
     *     @OA\Response(response=503, description="SMS yuborilmadi (Eskiz xatosi)")
     * )
     */
    private function _swaggerPhoneStart(): void {}

    /**
     * verify() — POST /auth/phone/verify
     * @OA\Post(
     *     path="/auth/phone/verify",
     *     tags={"Auth"},
     *     summary="OTP kodni tasdiqlash",
     *     description="SMS dan kelgan 6 xonali kodni tekshiradi. Muvaffaqiyatli bo'lsa /auth/register/complete ga o'tiladi.",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone","code"},
     *             @OA\Property(property="phone", type="string", example="+998901234567"),
     *             @OA\Property(property="code", type="string", example="847291", description="SMS dan kelgan 6 xonali kod")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Telefon tasdiqlandi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Phone verified. Proceed to complete registration."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="phone", type="string", example="+998901234567")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Kod noto'g'ri yoki muddati o'tgan"),
     *     @OA\Response(response=429, description="Ko'p noto'g'ri urinish")
     * )
     */
    private function _swaggerPhoneVerify(): void {}

    /**
     * complete() — POST /auth/register/complete
     * @OA\Post(
     *     path="/auth/register/complete",
     *     tags={"Auth"},
     *     summary="Ro'yxatdan o'tishni yakunlash va JWT olish",
     *     description="Telefon OTP bilan tasdiqlanganidan so'ng parol o'rnatib ro'yxatdan o'tishni yakunlaydi. JWT token qaytaradi.",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone","password","password_confirmation"},
     *             @OA\Property(property="phone", type="string", example="+998901234567"),
     *             @OA\Property(property="fname", type="string", nullable=true, example="Ism"),
     *             @OA\Property(property="lname", type="string", nullable=true, example="Familiya"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Ro'yxatdan o'tildi, JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Telefon OTP bilan tasdiqlanmagan"),
     *     @OA\Response(response=422, description="Validatsiya xatosi yoki telefon band")
     * )
     */
    private function _swaggerPhoneComplete(): void {}

    /**
     * forgot() — POST /auth/phone/forgot
     * @OA\Post(
     *     path="/auth/phone/forgot",
     *     tags={"Auth"},
     *     summary="Parolni tiklash uchun OTP yuborish",
     *     description="Ro'yxatdan o'tgan telefon raqamiga parolni tiklash uchun 6 xonali OTP yuboradi.",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+998901234567", description="+998XXXXXXXXX")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP yuborildi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset OTP sent successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="phone", type="string", example="+998901234567")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Bu raqam bilan akkaunt topilmadi"),
     *     @OA\Response(response=429, description="Ko'p urinish — kutish kerak"),
     *     @OA\Response(response=503, description="SMS yuborilmadi")
     * )
     */
    private function _swaggerPhoneForgot(): void {}

    /**
     * resetPassword() — POST /auth/phone/reset-password
     * @OA\Post(
     *     path="/auth/phone/reset-password",
     *     tags={"Auth"},
     *     summary="OTP bilan parolni tiklash",
     *     description="SMS dan kelgan OTP kodni tekshirib yangi parol o'rnatadi.",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone","code","password","password_confirmation"},
     *             @OA\Property(property="phone", type="string", example="+998901234567"),
     *             @OA\Property(property="code", type="string", example="847291", description="SMS dan kelgan 6 xonali kod"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Parol yangilandi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password reset successfully. Please login.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Kod noto'g'ri yoki muddati o'tgan"),
     *     @OA\Response(response=403, description="Telefon OTP bilan tasdiqlanmagan"),
     *     @OA\Response(response=429, description="Ko'p noto'g'ri urinish")
     * )
     */
    private function _swaggerPhoneResetPassword(): void {}
}
