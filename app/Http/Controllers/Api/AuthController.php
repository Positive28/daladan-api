<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone'    => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6',
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|unique:users,email',
            'telegram' => 'nullable|string|max:80',
            'region_id' => 'required|integer|exists:regions,id',
            'city_id'   => 'required|integer|exists:cities,id',
        ]);

        $validated['role'] = User::ROLE_USER;
        User::create($validated);
        $token = auth('api')->attempt([
            'phone'    => $request->phone,
            'password' => $request->password,
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => auth('api')->user(),
        ], 201);
    }

    
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone'    => ['required', 'string'],
            'password' => ['required'],
        ]);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->errorJson('Telefon raqam yoki parol noto\'g\'ri.', 401);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user(),
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }
}
