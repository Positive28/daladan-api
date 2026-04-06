<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return response()->json(response()->successJson($users));
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with(['region:id,name_uz', 'city:id,name_uz', 'ads'])
            ->where('role', User::ROLE_USER)
            ->find($id);

        if (!$user) {
            return response()->errorJson('Foydalanuvchi topilmadi.', 404);
        }

        return response()->json(response()->successJson($user));
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::where('role', User::ROLE_USER)->find($id);

        if (!$user) {
            return response()->errorJson('Foydalanuvchi topilmadi.', 404);
        }

        $user->delete();

        return response()->json(response()->successJson(['message' => 'Foydalanuvchi o\'chirildi.']));
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
