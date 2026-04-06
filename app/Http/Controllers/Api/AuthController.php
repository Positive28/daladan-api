<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->merge(['auth_type' => $request->query('auth_type', 'password')]);

        $validated = $request->validate([
            'phone'     => 'required|string|max:20|unique:users,phone',
            'password'  => 'required|string|min:6',
            'fname'     => 'required|string|max:255',
            'lname'     => 'required|string|max:255',
            'email'       => 'nullable|email|unique:users,email',
            'telegram'    => 'nullable|string|max:80',
            'telegram_id' => 'nullable|integer|unique:users,telegram_id',
            'region_id'   => 'nullable|integer|exists:regions,id',
            'city_id'     => 'nullable|integer|exists:cities,id',
        ], [
            'phone.unique' => 'Bunday nomer mavjud.',
        ]);

        $validated['role'] = User::ROLE_USER;
        $user = User::create($validated);

        $token = auth('api')->login($user);
        if (!$token) {
            return response()->json(['message' => 'Registration succeeded, but token creation failed.'], 500);
        }

        $user->load(['region', 'city']);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => $user,
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
        $user->load(['region', 'city']);

        return response()->json($user);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        $user = auth('api')->user();
        $user->load(['region', 'city']);

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
     * register() — POST /register
     * @OA\Post(
     *     path="/register",
     *     tags={"Auth"},
     *     summary="Yangi foydalanuvchini ro'yxatdan o'tkazish",
     *     @OA\Parameter(name="auth_type", in="query", required=true,
     *         description="Registratsiya turi: password yoki telegram",
     *         @OA\Schema(type="string", enum={"password","telegram"}, default="password")
     *     ),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone","password","fname","lname"},
     *             @OA\Property(property="phone",     type="string",  example="+998901234567"),
     *             @OA\Property(property="password",  type="string",  example="parol123"),
     *             @OA\Property(property="fname",     type="string",  example="Ism"),
     *             @OA\Property(property="lname",     type="string",  example="Familiya"),
     *             @OA\Property(property="region_id", type="integer", example=1),
     *             @OA\Property(property="city_id",   type="integer", example=10),
     *             @OA\Property(property="email",       type="string",  nullable=true),
     *             @OA\Property(property="telegram",    type="string",  nullable=true),
     *             @OA\Property(property="telegram_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Muvaffaqiyatli ro'yxatdan o'tdi, token qaytdi",
     *         @OA\JsonContent(ref="#/components/schemas/AuthTokenResponse")
     *     ),
     *     @OA\Response(response=422, description="Validatsiya xatosi")
     * )
     */
    private function _swaggerRegister(): void {}

    /**
     * login() — POST /login
     * @OA\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="Telefon raqam va parol bilan login",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"phone","password"},
     *             @OA\Property(property="phone",    type="string", example="+998901234567"),
     *             @OA\Property(property="password", type="string", example="parol123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Muvaffaqiyatli login, JWT token qaytadi",
     *         @OA\JsonContent(ref="#/components/schemas/AuthTokenResponse")
     *     ),
     *     @OA\Response(response=401, description="Telefon raqam yoki parol noto'g'ri"),
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
     *     @OA\Property(property="region_id", type="integer", nullable=true, example=1),
     *     @OA\Property(property="city_id", type="integer", nullable=true, example=10)
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
