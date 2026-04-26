<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

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
}
