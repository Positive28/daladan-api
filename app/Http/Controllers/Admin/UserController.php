<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'admin']);
    }

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with(['region:id,name_uz', 'city:id,name_uz'])
            ->where('role', User::ROLE_USER)
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return response()->successJson($users);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with(['region:id,name_uz', 'city:id,name_uz', 'ads'])
            ->where('role', User::ROLE_USER)
            ->find($id);

        if (!$user) {
            return response()->errorJson('Foydalanuvchi topilmadi.', 404);
        }

        return response()->successJson($user);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fname'       => 'nullable|string|max:100',
            'lname'       => 'nullable|string|max:100',
            'phone'       => 'required|string|max:20|unique:users,phone',
            'email'       => 'nullable|email|max:150|unique:users,email',
            'password'    => 'required|string|min:6',
            'telegram'    => 'nullable|string|max:100',
            'telegram_id' => 'nullable|integer|unique:users,telegram_id',
            'region_id'   => 'nullable|integer|exists:regions,id',
            'city_id'     => 'nullable|integer|exists:cities,id',
            'role'        => ['nullable', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['role'] ??= User::ROLE_USER;

        $user = User::create($data);
        $user->load(['region:id,name_uz', 'city:id,name_uz']);

        return response()->successJson($user, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::where('role', User::ROLE_USER)->find($id);

        if (!$user) {
            return response()->errorJson('Foydalanuvchi topilmadi.', 404);
        }

        $data = $request->validate([
            'fname'       => 'sometimes|nullable|string|max:100',
            'lname'       => 'sometimes|nullable|string|max:100',
            'phone'       => ['sometimes', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'email'       => ['sometimes', 'nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password'    => 'sometimes|string|min:6',
            'telegram'    => 'sometimes|nullable|string|max:100',
            'telegram_id' => ['sometimes', 'nullable', 'integer', Rule::unique('users', 'telegram_id')->ignore($user->id)],
            'region_id'   => 'sometimes|nullable|integer|exists:regions,id',
            'city_id'     => 'sometimes|nullable|integer|exists:cities,id',
            'role'        => ['sometimes', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        $user->load(['region:id,name_uz', 'city:id,name_uz']);

        return response()->successJson($user);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::where('role', User::ROLE_USER)->find($id);

        if (!$user) {
            return response()->errorJson('Foydalanuvchi topilmadi.', 404);
        }

        $user->delete();

        return response()->successJson(['message' => 'Foydalanuvchi o\'chirildi.']);
    }

    // =========================================================================
    // Swagger / OpenAPI annotations
    // =========================================================================

    /**
     * index() — GET /admin/users
     * @OA\Get(
     *     path="/admin/users",
     *     tags={"Admin Users"},
     *     summary="Registratsiyadan o'tgan foydalanuvchilar ro'yxati",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Userlar ro'yxati (pagination)",
     *         @OA\JsonContent(ref="#/components/schemas/AdminUserListResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden — faqat admin")
     * )
     */
    private function _swaggerIndex(): void {}

    /**
     * show() — GET /admin/users/{user}
     * @OA\Get(
     *     path="/admin/users/{user}",
     *     tags={"Admin Users"},
     *     summary="Bitta foydalanuvchini ko'rish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User topildi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminUserDetailResponse")
     *     ),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden — faqat admin")
     * )
     */
    private function _swaggerShow(): void {}

    /**
     * store() — POST /admin/users
     * @OA\Post(
     *     path="/admin/users",
     *     tags={"Admin Users"},
     *     summary="Yangi foydalanuvchi yaratish",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","password"},
     *             @OA\Property(property="fname",       type="string",  example="Ali"),
     *             @OA\Property(property="lname",       type="string",  example="Valiyev"),
     *             @OA\Property(property="phone",       type="string",  example="+998901234567"),
     *             @OA\Property(property="email",       type="string",  example="ali@example.com"),
     *             @OA\Property(property="password",    type="string",  example="secret123"),
     *             @OA\Property(property="telegram",    type="string",  example="ali_uz"),
     *             @OA\Property(property="telegram_id", type="integer", example=123456789),
     *             @OA\Property(property="region_id",   type="integer", example=1),
     *             @OA\Property(property="city_id",     type="integer", example=5),
     *             @OA\Property(property="role",        type="string",  enum={"user","admin"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Yaratildi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminUserDetailResponse")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden — faqat admin")
     * )
     */
    private function _swaggerStore(): void {}

    /**
     * update() — PUT /admin/users/{user}
     * @OA\Put(
     *     path="/admin/users/{user}",
     *     tags={"Admin Users"},
     *     summary="Foydalanuvchini tahrirlash",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="fname",       type="string",  example="Ali"),
     *             @OA\Property(property="lname",       type="string",  example="Valiyev"),
     *             @OA\Property(property="phone",       type="string",  example="+998901234567"),
     *             @OA\Property(property="email",       type="string",  example="ali@example.com"),
     *             @OA\Property(property="password",    type="string",  example="newpassword"),
     *             @OA\Property(property="telegram",    type="string",  example="ali_uz"),
     *             @OA\Property(property="telegram_id", type="integer", example=123456789),
     *             @OA\Property(property="region_id",   type="integer", example=1),
     *             @OA\Property(property="city_id",     type="integer", example=5),
     *             @OA\Property(property="role",        type="string",  enum={"user","admin"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Yangilandi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminUserDetailResponse")
     *     ),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden — faqat admin")
     * )
     */
    private function _swaggerUpdate(): void {}

    /**
     * destroy() — DELETE /admin/users/{user}
     * @OA\Delete(
     *     path="/admin/users/{user}",
     *     tags={"Admin Users"},
     *     summary="Foydalanuvchini o'chirish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="O'chirildi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="ok"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Foydalanuvchi o'chirildi.")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden — faqat admin")
     * )
     */
    private function _swaggerDestroy(): void {}

    /**
     * @OA\Tag(
     *     name="Admin Users",
     *     description="Admin: registratsiyadan o'tgan userlarni boshqarish"
     * )
     * @OA\Schema(
     *     schema="AdminUserListItem",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="fname", type="string", nullable=true),
     *     @OA\Property(property="lname", type="string", nullable=true),
     *     @OA\Property(property="phone", type="string", example="+998901234567"),
     *     @OA\Property(property="role", type="string", example="user"),
     *     @OA\Property(property="region_id", type="integer", nullable=true),
     *     @OA\Property(property="city_id", type="integer", nullable=true)
     * )
     * @OA\Schema(
     *     schema="AdminUserListResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=100),
     *         @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/AdminUserListItem")
     *         )
     *     )
     * )
     * @OA\Schema(
     *     schema="AdminUserDetailResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(property="data", ref="#/components/schemas/AdminUserListItem")
     * )
     */
    private function _swaggerSchemas(): void {}
}
