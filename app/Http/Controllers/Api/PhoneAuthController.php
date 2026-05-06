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
     *     summary="Telefon uchun OTP yuborish (+998XXXXXXXXX)",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+998901234567", description="^\\+998\\d{9}$")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OTP yuborildi"),
     *     @OA\Response(response=422, description="Noto'g'ri telefon")
     * )
     */
    private function _swaggerPhoneStart(): void {}

    /**
     * verify() — POST /auth/phone/verify
     * @OA\Post(
     *     path="/auth/phone/verify",
     *     tags={"Auth"},
     *     summary="OTP kodni tekshirish",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone","code"},
     *             @OA\Property(property="phone", type="string", example="+998901234567"),
     *             @OA\Property(property="code", type="string", example="123456", description="6 raqam")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Telefon tasdiqlandi, keyin /auth/register/complete"),
     *     @OA\Response(response=422, description="Kod noto'g'ri")
     * )
     */
    private function _swaggerPhoneVerify(): void {}

    /**
     * complete() — POST /auth/register/complete
     * @OA\Post(
     *     path="/auth/register/complete",
     *     tags={"Auth"},
     *     summary="Telefon tasdiqidan keyin parol bilan ro'yxatni yakunlash + JWT",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone","password","password_confirmation"},
     *             @OA\Property(property="phone", type="string", example="+998901234567"),
     *             @OA\Property(property="fname", type="string", nullable=true),
     *             @OA\Property(property="lname", type="string", nullable=true),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Token va user"),
     *     @OA\Response(response=403, description="Telefon avval OTP bilan tasdiqlanmagan"),
     *     @OA\Response(response=422, description="Validatsiya")
     * )
     */
    private function _swaggerPhoneComplete(): void {}
}
