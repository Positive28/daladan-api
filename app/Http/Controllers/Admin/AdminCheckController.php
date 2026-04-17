<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        if ($ad->status !== Ad::STATUS_PENDING) {
            return response()->errorJson('Faqat kutilayotgan (pending) e\'lon tasdiqlanishi mumkin.', 422);
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

        if ($ad->status !== Ad::STATUS_PENDING) {
            return response()->errorJson('Faqat kutilayotgan (pending) e\'lon rad etilishi mumkin.', 422);
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
     * Pending e'lonni tahrirlash (status pending bo'lib qoladi).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $record = Ad::find($id);

        if (!$record) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        if ($record->status !== Ad::STATUS_PENDING) {
            return response()->errorJson('Faqat kutilayotgan (pending) e\'lon tahrirlanishi mumkin.', 422);
        }

        $newCategoryId = $request->filled('category_id')
            ? (int) $request->input('category_id')
            : (int) $record->category_id;

        $rules = [
            'category_id'        => 'sometimes|integer|exists:categories,id',
            'subcategory_id'     => ['sometimes', 'integer'],
            'title'              => 'sometimes|string|max:150',
            'description'        => 'nullable|string',
            'district'           => 'nullable|string|max:100',
            'price'              => 'nullable|integer|min:0',
            'quantity'           => 'nullable|numeric|min:0',
            'unit'               => 'nullable|string|max:20',
            'media'              => 'nullable|array',
            'media.*'            => 'file|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime|max:51200',
            'delete_media_ids'   => 'nullable|array',
            'delete_media_ids.*' => 'integer|exists:media,id',
        ];

        if ($request->filled('subcategory_id')) {
            $rules['subcategory_id'][] = Rule::exists('subcategories', 'id')
                ->where(fn ($q) => $q->where('category_id', $newCategoryId));
        }

        $validated = $request->validate($rules, [
            'subcategory_id.exists' => 'Subkategoriya tanlangan kategoriyaga tegishli emas.',
        ]);

        $adFields = array_diff_key($validated, array_flip(['media', 'delete_media_ids']));
        if ($adFields !== []) {
            $record->update($adFields);
        }

        if (!empty($validated['delete_media_ids'])) {
            foreach ($validated['delete_media_ids'] as $mediaId) {
                $record->getMedia('gallery')->where('id', $mediaId)->first()?->delete();
            }
        }

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $record->addMedia($file)->toMediaCollection('gallery');
            }
        }

        $record->refresh()->load(['category:id,name', 'subcategory:id,name', 'seller.region', 'seller.city']);

        return response()->successJson($record);
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
     * update() — PATCH /admin/ads/{id}/edit (JSON yoki multipart/form-data + media)
     * @OA\Patch(
     *     path="/admin/ads/{id}/edit",
     *     tags={"Admin Ads Moderation"},
     *     summary="Pending e'lonni tahrirlash",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=101)),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/AdminCheckAdUpdatePayload")
     *         ),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="category_id", type="integer", example=4),
     *                 @OA\Property(property="subcategory_id", type="integer", example=11),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="district", type="string", nullable=true),
     *                 @OA\Property(property="price", type="integer", nullable=true),
     *                 @OA\Property(property="quantity", type="number", format="float", nullable=true),
     *                 @OA\Property(property="unit", type="string", nullable=true),
     *                 @OA\Property(property="delete_media_ids", type="array", @OA\Items(type="integer")),
     *                 @OA\Property(property="media", type="array", @OA\Items(type="string", format="binary"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Yangilandi (status: pending)",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCheckAdResponse")
     *     ),
     *     @OA\Response(response=422, description="Faqat pending yoki validatsiya"),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    private function _swaggerUpdate(): void {}

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
     *     @OA\Response(response=422, description="Faqat pending e'lon tasdiqlanadi"),
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
     *     @OA\Response(response=422, description="Faqat pending yoki validatsiya"),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    private function _swaggerReject(): void {}

    /**
     * @OA\Tag(
     *     name="Admin Ads Moderation",
     *     description="Admin tomonidan e'lonlarni ko'rish, tahrirlash (pending), tasdiqlash/rad etish"
     * )
     * @OA\Schema(
     *     schema="AdminCheckAdUpdatePayload",
     *     type="object",
     *     @OA\Property(property="category_id", type="integer", example=4),
     *     @OA\Property(property="subcategory_id", type="integer", example=11),
     *     @OA\Property(property="title", type="string", maxLength=150),
     *     @OA\Property(property="description", type="string", nullable=true),
     *     @OA\Property(property="district", type="string", nullable=true, maxLength=100),
     *     @OA\Property(property="price", type="integer", nullable=true),
     *     @OA\Property(property="quantity", type="number", format="float", nullable=true),
     *     @OA\Property(property="unit", type="string", nullable=true, maxLength=20),
     *     @OA\Property(property="delete_media_ids", type="array", @OA\Items(type="integer"))
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
