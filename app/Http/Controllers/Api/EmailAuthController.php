<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

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
}
