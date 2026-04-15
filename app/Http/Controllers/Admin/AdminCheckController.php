<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class AdminCheckController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'admin']);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $allowedStatuses = [
            Ad::STATUS_PENDING,
            Ad::STATUS_ACTIVE,
            Ad::STATUS_REJECTED,
            Ad::STATUS_SOLD,
            Ad::STATUS_DELETED,
        ];

        $status = (string) $request->input('status', Ad::STATUS_PENDING);

        $ads = Ad::query()
            ->with(['seller:id,fname,lname,phone', 'category:id,name', 'subcategory:id,name'])
            ->when(
                in_array($status, $allowedStatuses, true),
                fn ($query) => $query->where('status', $status),
                fn ($query) => $query->where('status', Ad::STATUS_PENDING)
            )
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->successJson($ads);
    }

    public function show(string $id): JsonResponse
    {
        $ad = Ad::query()
            ->with(['seller:id,fname,lname,phone,region_id,city_id', 'category:id,name', 'subcategory:id,name'])
            ->find($id);

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        return response()->successJson($ad);
    }

    public function approve(string $id): JsonResponse
    {
        $ad = Ad::find($id);

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        $ad->update([
            'status' => Ad::STATUS_ACTIVE,
            'reject_reason' => null,
        ]);

        return response()->successJson([
            'message' => 'E\'lon tasdiqlandi.',
            'status' => $ad->status,
            'ad_id' => $ad->id,
        ]);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $ad = Ad::find($id);

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $ad->update([
            'status' => Ad::STATUS_REJECTED,
            'reject_reason' => $validated['reason'],
        ]);

        return response()->successJson([
            'message' => 'E\'lon rad etildi.',
            'status' => $ad->status,
            'ad_id' => $ad->id,
            'reject_reason' => $ad->reject_reason,
        ]);
    }

    /**
     * index() — GET /admin/ads
     * @OA\Get(
     *     path="/admin/ads",
     *     tags={"Admin Ads Moderation"},
     *     summary="Moderatsiya uchun e'lonlar ro'yxati",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter status (default: pending)",
     *         @OA\Schema(type="string", enum={"pending","active","rejected","sold","deleted"}, example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="E'lonlar ro'yxati",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCheckAdListResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    private function _swaggerIndex(): void {}

    /**
     * show() — GET /admin/ads/{id}
     * @OA\Get(
     *     path="/admin/ads/{id}",
     *     tags={"Admin Ads Moderation"},
     *     summary="Bitta e'lonni tekshirish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=101)),
     *     @OA\Response(
     *         response=200,
     *         description="E'lon topildi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCheckAdResponse")
     *     ),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    private function _swaggerShow(): void {}

    /**
     * approve() — PATCH /admin/ads/{id}/approve
     * @OA\Patch(
     *     path="/admin/ads/{id}/approve",
     *     tags={"Admin Ads Moderation"},
     *     summary="E'lonni tasdiqlash (active qilish)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=101)),
     *     @OA\Response(
     *         response=200,
     *         description="E'lon tasdiqlandi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCheckActionResponse")
     *     ),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    private function _swaggerApprove(): void {}

    /**
     * reject() — PATCH /admin/ads/{id}/reject
     * @OA\Patch(
     *     path="/admin/ads/{id}/reject",
     *     tags={"Admin Ads Moderation"},
     *     summary="E'lonni rad etish va sabab yozish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=101)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AdminCheckRejectPayload")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="E'lon rad etildi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCheckActionResponse")
     *     ),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    private function _swaggerReject(): void {}

    /**
     * @OA\Tag(
     *     name="Admin Ads Moderation",
     *     description="Admin tomonidan e'lonlarni tasdiqlash/rad etish endpointlari"
     * )
     * @OA\Schema(
     *     schema="AdminCheckRejectPayload",
     *     type="object",
     *     required={"reason"},
     *     @OA\Property(property="reason", type="string", maxLength=1000, example="Qoidaga zid kontent.")
     * )
     * @OA\Schema(
     *     schema="AdminCheckActionData",
     *     type="object",
     *     @OA\Property(property="message", type="string", example="E'lon tasdiqlandi."),
     *     @OA\Property(property="status", type="string", example="active"),
     *     @OA\Property(property="ad_id", type="integer", example=101),
     *     @OA\Property(property="reject_reason", type="string", nullable=true, example=null)
     * )
     * @OA\Schema(
     *     schema="AdminCheckActionResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(property="data", ref="#/components/schemas/AdminCheckActionData")
     * )
     * @OA\Schema(
     *     schema="AdminCheckAd",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=101),
     *     @OA\Property(property="seller_id", type="integer", example=12),
     *     @OA\Property(property="category_id", type="integer", example=4),
     *     @OA\Property(property="subcategory_id", type="integer", example=11),
     *     @OA\Property(property="title", type="string", example="Naslli echkilar"),
     *     @OA\Property(property="status", type="string", example="pending"),
     *     @OA\Property(property="reject_reason", type="string", nullable=true, example="Qoidaga zid kontent."),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * @OA\Schema(
     *     schema="AdminCheckAdResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(property="data", ref="#/components/schemas/AdminCheckAd")
     * )
     * @OA\Schema(
     *     schema="AdminCheckAdListResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=42),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AdminCheckAd"))
     *     )
     * )
     */
    private function _swaggerSchemas(): void {}
}
