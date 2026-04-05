<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class AdsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $ads = $request->user()
            ->ads()
            ->with(['category', 'subcategory', 'seller.region', 'seller.city'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->successJson($ads);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->region_id || !$user->city_id) {
            return response()->errorJson('E\'lon joylash uchun avval profilingizda viloyat va shaharni belgilang.', 422);
        }

        $validated = $request->validate([
            'category_id'    => 'required|integer|exists:categories,id',
            'subcategory_id' => [
                'required', 'integer',
                Rule::exists('subcategories', 'id')
                    ->where(fn ($q) => $q->where('category_id', $request->input('category_id'))),
            ],
            'title'       => 'required|string|max:150',
            'description' => 'nullable|string',
            'district'    => 'nullable|string|max:100',
            'price'       => 'nullable|integer|min:0',
            'quantity'    => 'nullable|numeric|min:0',
            'unit'        => 'nullable|string|in:kg,ton,bag,box,piece',
            'media'       => 'nullable|array',
            'media.*'     => 'file|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime|max:51200',
        ], [
            'subcategory_id.exists' => 'Subkategoriya tanlangan kategoriyaga tegishli emas.',
        ]);

        $ad = Ad::create([
            ...$validated,
            'seller_id' => $user->id,
            'region_id' => $user->region_id,
            'city_id'   => $user->city_id,
            'status'    => 'active',
        ]);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $ad->addMedia($file)->toMediaCollection('gallery');
            }
        }

        $ad->load(['category', 'subcategory', 'seller.region', 'seller.city']);

        return response()->successJson($ad, 201);
    }

    public function show(Request $request, string $ad): JsonResponse
    {
        $model = Ad::with(['category', 'subcategory', 'seller.region', 'seller.city'])
            ->where('id', $ad)
            ->where('seller_id', $request->user()->id)
            ->first();

        if (!$model) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        return response()->successJson($model);
    }

    public function update(Request $request, string $ad): JsonResponse
    {
        $record = Ad::where('id', $ad)
            ->where('seller_id', $request->user()->id)
            ->first();

        if (!$record) {
            return response()->errorJson('E\'lon topilmadi.', 404);
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
            'unit'               => 'nullable|string|in:kg,ton,bag,box,piece',
            'status'             => 'sometimes|string|in:active,sold,deleted',
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
        if ($adFields) {
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

        return response()->successJson(
            $record->fresh(['category', 'subcategory', 'seller.region', 'seller.city'])
        );
    }

    public function destroy(Request $request, string $ad): JsonResponse
    {
        $record = Ad::where('id', $ad)
            ->where('seller_id', $request->user()->id)
            ->first();  

        if (!$record) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        $record->delete();

        return response()->successJson(['message' => 'E\'lon o\'chirildi.']);
    }

    // =========================================================================
    // Swagger / OpenAPI annotations
    // =========================================================================

    /** index() — GET /profile/ads
     * @OA\Get(
     *     path="/profile/ads",
     *     tags={"Profile","Ads"},
     *     summary="O'z reklamalari ro'yxati",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=15)),
     *     @OA\Response(response=200, description="E'lonlar ro'yxati"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */

    /** store() — POST /profile/ads
     * @OA\Post(
     *     path="/profile/ads",
     *     tags={"Profile","Ads"},
     *     summary="Yangi e'lon yaratish",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id","subcategory_id","title"},
     *                 @OA\Property(property="category_id",    type="integer"),
     *                 @OA\Property(property="subcategory_id", type="integer"),
     *                 @OA\Property(property="title",          type="string"),
     *                 @OA\Property(property="description",    type="string",  nullable=true),
     *                 @OA\Property(property="district",       type="string",  nullable=true),
     *                 @OA\Property(property="price",          type="integer", nullable=true),
     *                 @OA\Property(property="quantity",       type="number",  nullable=true),
     *                 @OA\Property(property="unit",           type="string",  nullable=true, enum={"kg","ton","bag","box","piece"}),
     *                 @OA\Property(property="media[]",        type="array",   @OA\Items(type="string", format="binary"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="E'lon yaratildi"),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */

    /** show() — GET /profile/ads/{ad}
     * @OA\Get(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="Bitta o'z reklamasini ko'rish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ad", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="E'lon"),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */

    /** update() — POST /profile/ads/{ad}  (yoki PUT / PATCH)
     * @OA\Post(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="E'lonni yangilash (form-data/media uchun POST, JSON uchun PUT/PATCH)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ad", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="category_id",    type="integer"),
     *                 @OA\Property(property="subcategory_id", type="integer"),
     *                 @OA\Property(property="title",          type="string"),
     *                 @OA\Property(property="description",    type="string",  nullable=true),
     *                 @OA\Property(property="district",       type="string",  nullable=true),
     *                 @OA\Property(property="price",          type="integer", nullable=true),
     *                 @OA\Property(property="quantity",       type="number",  nullable=true),
     *                 @OA\Property(property="unit",           type="string",  nullable=true, enum={"kg","ton","bag","box","piece"}),
     *                 @OA\Property(property="status",         type="string",  enum={"active","sold","deleted"}),
     *                 @OA\Property(property="delete_media_ids[]", type="array", @OA\Items(type="integer")),
     *                 @OA\Property(property="media[]",        type="array",   @OA\Items(type="string", format="binary"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="E'lon yangilandi"),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @OA\Put(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="PUT alias (JSON body)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ad", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Yangilandi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @OA\Patch(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="PATCH alias (JSON body)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ad", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Yangilandi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */

    /** destroy() — DELETE /profile/ads/{ad}
     * @OA\Delete(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="O'z reklamasini o'chirish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ad", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="O'chirildi"),
     *     @OA\Response(response=404, description="Topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
}
