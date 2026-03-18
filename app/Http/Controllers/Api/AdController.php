<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;


class AdController extends Controller
{
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
     *         @OA\JsonContent(
     *             required={"category_id","subcategory_id","title","price","quantity","unit"},
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="subcategory_id", type="integer", example=10),
     *             @OA\Property(property="district", type="string", nullable=true),
     *             @OA\Property(property="title", type="string", example="Olma sotiladi"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="price", type="number", format="decimal", example=15000),
     *             @OA\Property(property="quantity", type="number", format="decimal", example=100),
     *             @OA\Property(property="quantity_description", type="string", nullable=true),
     *             @OA\Property(property="unit", type="string", example="kg"),
     *             @OA\Property(property="delivery_info", type="string", nullable=true),
     *             @OA\Property(property="media", type="array", items=@OA\Items(type="string", format="binary"), nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="E'lon yaratildi"),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {   
        // dd($request->all());
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

        // return response()->json(response()->successJson($ad), 201);

        return response()->json($ad, 201);
    }

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
