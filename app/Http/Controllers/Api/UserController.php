<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    public function profile(): JsonResponse
    {
        $user = auth('api')->user();
        $user->load(['region', 'city']);
        $user->load([
            'ads' => fn ($q) => $q
                ->with(['category:id,name', 'subcategory:id,name'])
                ->orderByDesc('created_at')
                ->limit(10),
        ]);

        return response()->json($user);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $validated = $request->validate([
            'fname'     => 'sometimes|string|max:255',
            'lname'     => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|unique:users,email,' . $user->id,
            'telegram'  => 'sometimes|nullable|string|max:80',
            'region_id' => 'sometimes|nullable|integer|exists:regions,id',
            'city_id'   => 'sometimes|nullable|integer|exists:cities,id',
        ]);

        $user->update($validated);
        $user->load(['region', 'city']);

        return response()->json(['message' => 'Profil yangilandi', 'user' => $user]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = auth('api')->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'message'    => 'Avatar yangilandi',
            'avatar_url' => Storage::url($path),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $user = auth('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Hozirgi parol noto\'g\'ri'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Parol muvaffaqiyatli o\'zgartirildi']);
    }

    // =========================================================================
    // Swagger / OpenAPI annotations
    // =========================================================================

    /**
     * profile() — GET /profile
     * @OA\Get(
     *     path="/profile",
     *     tags={"Profile"},
     *     summary="O'zi haqida ma'lumot",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profil ma'lumotlari",
     *         @OA\JsonContent(ref="#/components/schemas/ProfileUser")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerProfile(): void {}

    /**
     * updateProfile() — PUT /profile
     * @OA\Put(
     *     path="/profile",
     *     tags={"Profile"},
     *     summary="Profilni yangilash",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="fname",     type="string",  nullable=true),
     *             @OA\Property(property="lname",     type="string",  nullable=true),
     *             @OA\Property(property="email",     type="string",  nullable=true),
     *             @OA\Property(property="telegram",  type="string",  nullable=true),
     *             @OA\Property(property="region_id", type="integer", nullable=true),
     *             @OA\Property(property="city_id",   type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil yangilandi",
     *         @OA\JsonContent(ref="#/components/schemas/ProfileUserResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validatsiya xatosi")
     * )
     */
    private function _swaggerUpdateProfile(): void {}

    /**
     * updateAvatar() — POST /profile/avatar
     * @OA\Post(
     *     path="/profile/avatar",
     *     tags={"Profile"},
     *     summary="Avatarni yangilash",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"avatar"},
     *                 @OA\Property(property="avatar", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar yangilandi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Avatar yangilandi"),
     *             @OA\Property(property="avatar_url", type="string", example="/storage/avatars/file.jpg")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validatsiya xatosi")
     * )
     */
    private function _swaggerUpdateAvatar(): void {}

    /**
     * updatePassword() — PUT /profile/password
     * @OA\Put(
     *     path="/profile/password",
     *     tags={"Profile"},
     *     summary="Parolni o'zgartirish",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password",          type="string"),
     *             @OA\Property(property="new_password",              type="string"),
     *             @OA\Property(property="new_password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Parol muvaffaqiyatli o'zgartirildi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Parol muvaffaqiyatli o'zgartirildi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validatsiya xatosi / parol mos kelmadi")
     * )
     */
    private function _swaggerUpdatePassword(): void {}

    /**
     * @OA\Tag(
     *     name="Profile",
     *     description="Autentifikatsiyalangan foydalanuvchi profili"
     * )
     * @OA\Schema(
     *     schema="ProfileAdShort",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=101),
     *     @OA\Property(property="category_id", type="integer", example=4),
     *     @OA\Property(property="subcategory_id", type="integer", example=11),
     *     @OA\Property(property="title", type="string", example="Echkilar sotiladi"),
     *     @OA\Property(property="price", type="integer", nullable=true, example=1500000),
     *     @OA\Property(property="status", type="string", example="active"),
     *     @OA\Property(property="created_at", type="string", format="date-time")
     * )
     * @OA\Schema(
     *     schema="ProfileUser",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="fname", type="string", nullable=true, example="Ali"),
     *     @OA\Property(property="lname", type="string", nullable=true, example="Valiyev"),
     *     @OA\Property(property="phone", type="string", example="+998901234567"),
     *     @OA\Property(property="telegram", type="string", nullable=true, example="@ali"),
     *     @OA\Property(property="email", type="string", nullable=true, example="ali@mail.com"),
     *     @OA\Property(property="role", type="string", example="user"),
     *     @OA\Property(property="region_id", type="integer", nullable=true, example=1),
     *     @OA\Property(property="city_id", type="integer", nullable=true, example=10),
     *     @OA\Property(property="avatar_url", type="string", nullable=true, example="http://daladan-api.loc/storage/avatars/abc.jpg"),
     *     @OA\Property(property="ads", type="array", @OA\Items(ref="#/components/schemas/ProfileAdShort"))
     * )
     * @OA\Schema(
     *     schema="ProfileUserResponse",
     *     type="object",
     *     @OA\Property(property="message", type="string", example="Profil yangilandi"),
     *     @OA\Property(property="user", ref="#/components/schemas/ProfileUser")
     * )
     */
    private function _swaggerSchemas(): void {}
}
