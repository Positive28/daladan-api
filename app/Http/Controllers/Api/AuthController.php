<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($validated['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [
            $field => $validated['identifier'],
            'password' => $validated['password'],
        ];

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->errorJson('Email/telefon yoki parol noto\'g\'ri.', 401);
        }

        $user = auth('api')->setToken($token)->user();
        if ($user && $user->status === User::STATUS_BLOCKED) {
            auth('api')->logout();
            return response()->errorJson('Sizning akkauntingiz bloklangan.', 403);
        }
        if ($user && $user->status === User::STATUS_PENDING) {
            auth('api')->logout();
            return response()->errorJson('Akkauntingiz tasdiqlanmagan. Iltimos, emailingizni tasdiqlang.', 403);
        }

        return $this->respondWithToken($token);
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->errorJson('Unauthorized', 401);
        }
        return response()->json($user);
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
        } catch (TokenBlacklistedException) {
            return response()->errorJson('Token allaqachon bekor qilingan (blacklist).', 401);
        } catch (TokenExpiredException) {
            return response()->errorJson('Token muddati o\'tgan, qaytadan login qiling.', 401);
        } catch (TokenInvalidException) {
            return response()->errorJson('Token noto\'g\'ri.', 401);
        } catch (JWTException) {
            return response()->errorJson('Token yangilab bo\'lmadi.', 401);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        // refresh() dan keyin eski token blacklist bo‘ladi; user ni yangi token bilan olish kerak
        $user = auth('api')->setToken($token)->user();

        if (! $user) {
            return response()->errorJson('Unauthorized', 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => $user,
        ]);
    }

    // =========================================================================
    // Swagger / OpenAPI annotations
    // =========================================================================

    /**
     * login() — POST /login
     * @OA\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="Email yoki telefon va parol bilan login",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"identifier","password"},
     *             @OA\Property(property="identifier",    type="string", example="+998901234567"),
     *             @OA\Property(property="password", type="string", example="parol123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Muvaffaqiyatli login, JWT token qaytadi",
     *         @OA\JsonContent(ref="#/components/schemas/AuthTokenResponse")
     *     ),
     *     @OA\Response(response=401, description="Email/telefon yoki parol noto'g'ri"),
     *     @OA\Response(response=422, description="Validatsiya xatosi")
     * )
     */
    private function _swaggerLogin(): void {}

    /**
     * logout() — POST /logout
     * @OA\Post(
     *     path="/logout",
     *     tags={"Auth"},
     *     summary="Tizimdan chiqish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Muvaffaqiyatli logout"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerLogout(): void {}

    /**
     * me() — GET /get-me
     * @OA\Get(
     *     path="/get-me",
     *     tags={"Auth"},
     *     summary="Hozirgi foydalanuvchi ma'lumotlari",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Foydalanuvchi ma'lumotlari",
     *         @OA\JsonContent(ref="#/components/schemas/AuthUser")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerMe(): void {}

    /**
     * refresh() — POST /refresh
     * @OA\Post(
     *     path="/refresh",
     *     tags={"Auth"},
     *     summary="Tokenni yangilash",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Yangi token qaytadi",
     *         @OA\JsonContent(ref="#/components/schemas/AuthTokenResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerRefresh(): void {}

    /**
     * @OA\Info(
     *     title="Start API",
     *     version="1.0.0",
     *     description="AgroBozor uchun backend API"
     * )
     * @OA\Server(url="https://api.daladan.uz/api/v1/", description="Production server")
     * @OA\Server(url="http://daladan-api.loc/api/v1", description="Local dev server (OSPanel)")
     * @OA\Server(url="http://localhost:8000/api/v1", description="php artisan serve")
     * @OA\SecurityScheme(
     *     securityScheme="bearerAuth",
     *     type="http",
     *     scheme="bearer",
     *     bearerFormat="JWT"
     * )
     * @OA\Schema(
     *     schema="AuthUser",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="fname", type="string", nullable=true, example="Ali"),
     *     @OA\Property(property="lname", type="string", nullable=true, example="Valiyev"),
     *     @OA\Property(property="phone", type="string", example="+998901234567"),
     *     @OA\Property(property="email", type="string", nullable=true, example="ali@mail.com"),
     *     @OA\Property(property="role", type="string", example="user"),
     * )
     * @OA\Schema(
     *     schema="AuthTokenResponse",
     *     type="object",
     *     @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1Qi..."),
     *     @OA\Property(property="token_type", type="string", example="bearer"),
     *     @OA\Property(property="expires_in", type="integer", example=3600),
     *     @OA\Property(property="user", ref="#/components/schemas/AuthUser")
     * )
     */
    private function _swaggerMetaSchemas(): void {}
}
