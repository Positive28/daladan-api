<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;


class AdController extends Controller
{
    /**
     * @OA\Get(
     *     path="/ads",
     *     tags={"Ads"},
     *     summary="Foydalanuvchining e'lonlari ro'yxati",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Sahifadagi elementlar soni",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="E'lonlar ro'yxati"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $ads = Ad::where('seller_id', $request->user()->id)
            ->with(['category', 'subcategory', 'seller.region', 'seller.city'])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->successJson($ads);
    }

    /**
     * @OA\Post(
     *     path="/profile/ads",
     *     tags={"Ads"},
     *     summary="Profil sahifasidan e'lon yaratish",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id","subcategory_id","title","price","quantity"},
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="subcategory_id", type="integer", example=10),
     *                 @OA\Property(property="district", type="string", nullable=true),
     *                 @OA\Property(property="title", type="string", example="Olma sotiladi"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="price", type="number", example=15000),
     *                 @OA\Property(property="quantity", type="number", example=100),
     *                 @OA\Property(property="quantity_description", type="string", nullable=true),
     *                 @OA\Property(property="unit", type="string", example="kg"),
     *                 @OA\Property(property="delivery_info", type="string", nullable=true),
     *                 @OA\Property(
     *                     property="media[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="E'lon yaratildi"),
     *     @OA\Response(response=422, description="Validatsiya xatosi yoki profil manzili to'ldirilmagan"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {   
        $user = $request->user();

        if (!$user->region_id || !$user->city_id) {
            return response()->errorJson(
                'E\'lon joylash uchun avval profilingizda manzilni (viloyat va shahar) belgilang.',
                422
            );
        }

        $validated = $request->validate([
            'category_id'          => 'required|integer|exists:categories,id',
            'subcategory_id'      => 'required|integer|exists:subcategories,id',
            'district'            => 'nullable|string|max:100',
            'title'               => 'required|string|max:150',
            'description'         => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'quantity'            => 'required|numeric|min:0',
            'quantity_description' => 'nullable|string|max:50',
            'unit'                => 'nullable|string|max:30',
            'delivery_info'       => 'nullable|string|max:255',
            'media'               => 'nullable|array',
            'media.*'             => 'file|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime|max:51200',
        ]);

        $validated['seller_id'] = $user->id;
        $validated['status'] = 'active';
        unset($validated['media']);

        $ad = Ad::create($validated);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $ad->addMedia($file)->toMediaCollection('gallery');
            }
        }

        $ad->load(['category', 'subcategory', 'seller.region', 'seller.city']);

        return response()->json($ad, 201);
    }

    /**
     * @OA\Get(
     *     path="/ads/{id}",
     *     tags={"Ads"},
     *     summary="Bitta e'lonni olish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="E'lon ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="E'lon topildi"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="E'lon topilmadi"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $ad = Ad::with(['category', 'subcategory', 'seller.region', 'seller.city'])
            ->where('id', $id)
            ->where('seller_id', $request->user()->id)
            ->first();

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        return response()->successJson($ad);
    }

    /**
     * @OA\Post(
     *     path="/ads/{id}",
     *     tags={"Ads"},
     *     summary="E'lonni yangilash (method spoofing: _method=PUT)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="E'lon ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="subcategory_id", type="integer", example=10),
     *                 @OA\Property(property="district", type="string", nullable=true),
     *                 @OA\Property(property="title", type="string", example="Yangi sarlavha"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="price", type="number", example=16000),
     *                 @OA\Property(property="quantity", type="number", example=80),
     *                 @OA\Property(property="quantity_description", type="string", nullable=true),
     *                 @OA\Property(property="unit", type="string", example="kg"),
     *                 @OA\Property(property="delivery_info", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string", enum={"active","sold","deleted"}),
     *                 @OA\Property(
     *                     property="delete_media_ids[]",
     *                     type="array",
     *                     @OA\Items(type="integer", example=5)
     *                 ),
     *                 @OA\Property(
     *                     property="media[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="E'lon yangilandi"),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     *
     * @OA\Put(
     *     path="/ads/{id}",
     *     tags={"Ads"},
     *     summary="E'lonni yangilash (JSON yoki form-data, faylsiz holat)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Yangi sarlavha"),
     *             @OA\Property(property="price", type="number", example=16000),
     *             @OA\Property(property="quantity", type="number", example=80),
     *             @OA\Property(property="status", type="string", enum={"active","sold","deleted"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="E'lon yangilandi"),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $ad = Ad::where('id', $id)->where('seller_id', $request->user()->id)->first();

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        $validated = $request->validate([
            'category_id'          => 'sometimes|integer|exists:categories,id',
            'subcategory_id'      => 'sometimes|integer|exists:subcategories,id',
            'district'            => 'nullable|string|max:100',
            'title'               => 'sometimes|string|max:150',
            'description'         => 'nullable|string',
            'price'               => 'sometimes|numeric|min:0',
            'quantity'            => 'sometimes|numeric|min:0',
            'quantity_description' => 'nullable|string|max:50',
            'unit'                => 'sometimes|string|max:30',
            'delivery_info'       => 'nullable|string|max:255',
            'status'              => 'sometimes|string|in:active,sold,deleted',
            'media'               => 'nullable|array',
            'media.*'             => 'file|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime|max:51200',
            'delete_media_ids'    => 'nullable|array',
            'delete_media_ids.*'  => 'integer|exists:media,id',
        ]);

        unset($validated['media'], $validated['delete_media_ids']);
        $ad->update($validated);

        if ($request->filled('delete_media_ids')) {
            foreach ($request->delete_media_ids as $mediaId) {
                $ad->getMedia('gallery')->where('id', $mediaId)->first()?->delete();
            }
        }

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $ad->addMedia($file)->toMediaCollection('gallery');
            }
        }

        return response()->successJson($ad->fresh(['category', 'subcategory', 'seller.region', 'seller.city']));
    }

    /**
     * @OA\Delete(
     *     path="/ads/{id}",
     *     tags={"Ads"},
     *     summary="E'lonni o'chirish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="E'lon ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="E'lon o'chirildi"),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $ad = Ad::where('id', $id)->where('seller_id', $request->user()->id)->first();

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        $ad->delete();

        return response()->successJson(['message' => 'E\'lon o\'chirildi.']);
    }
}
