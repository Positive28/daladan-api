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
        $allowed = [
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
                in_array($status, $allowed, true),
                fn ($q) => $q->where('status', $status),
                fn ($q) => $q->where('status', Ad::STATUS_PENDING)
            )
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->successJson($ads);
    }

    public function show(string $id): JsonResponse
    {
        $ad = Ad::query()
            ->with(['seller:id,fname,lname,phone', 'category:id,name', 'subcategory:id,name'])
            ->find($id);

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        return response()->successJson($ad);
    }

    /**
     * Moderatsiya: pending → active. Promo avtomatik yoqilmaydi;
     * keyin sotuvchi promotion_plan buyurtma qiladi va admin confirm qiladi.
     */
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

        $ad->refresh();

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

        $record->refresh()->load(['category:id,name', 'subcategory:id,name', 'seller']);

        return response()->successJson($record);
    }

    /**
     * @OA\Get(
     *     path="/admin/ads",
     *     tags={"Admin Ads Moderation"},
     *     summary="E'lonlar (moderatsiya ro'yxati)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="status", in="query", required=false, description="pending|active|rejected|sold|deleted", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Paginatsiyali e'lonlar"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerIndex(): void {}

    /**
     * @OA\Get(
     *     path="/admin/ads/{id}",
     *     tags={"Admin Ads Moderation"},
     *     summary="Bitta e'lon",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="E'lon obyekti"),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerShow(): void {}

    /**
     * @OA\Patch(
     *     path="/admin/ads/{id}/edit",
     *     tags={"Admin Ads Moderation"},
     *     summary="Pending e'lonni tahrirlash",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Yangilangan e'lon"),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=422, description="Faqat pending yoki validatsiya"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerUpdate(): void {}

    /**
     * @OA\Patch(
     *     path="/admin/ads/{id}/approve",
     *     tags={"Admin Ads Moderation"},
     *     summary="Pending e'lonni tasdiqlash (active)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tasdiq xabari"),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=422, description="Faqat pending"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerApprove(): void {}

    /**
     * @OA\Patch(
     *     path="/admin/ads/{id}/reject",
     *     tags={"Admin Ads Moderation"},
     *     summary="Pending e'lonni rad etish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"reason"}, @OA\Property(property="reason", type="string", maxLength=1000))),
     *     @OA\Response(response=200, description="Rad etildi"),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=422, description="Faqat pending yoki reason yo'q"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerReject(): void {}
}
