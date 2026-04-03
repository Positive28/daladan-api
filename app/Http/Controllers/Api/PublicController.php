<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Public",
 *     description="Front uchun ochiq (tokensiz) endpointlar"
 * )
 */
class PublicController extends Controller
{
    private const AD_DETAIL_RELATIONS = ['animal', 'poultry', 'grain', 'fruit', 'forage', 'vegetable'];

    /**
     * @OA\Get(
     *     path="/public/ads",
     *     tags={"Public"},
     *     summary="Barcha faol e'lonlar ro'yxati",
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subcategory_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginatsiyali e'lonlar (category, subcategory, seller, animal va h.k.)"
     *     )
     * )
     */
    public function ads(Request $request): JsonResponse
    {
        $query = Ad::query()
            ->where('status', 'active')
            ->with(['category', 'subcategory', 'seller.region', 'seller.city', ...self::AD_DETAIL_RELATIONS]);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->integer('subcategory_id'));
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $ads = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->successJson($ads);
    }

    /**
     * @OA\Get(
     *     path="/public/ads/{id}",
     *     tags={"Public"},
     *     summary="Bitta faol e'lon",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="E'lon obyekti"),
     *     @OA\Response(response=404, description="Topilmadi yoki faol emas")
     * )
     */
    public function ad(string $id): JsonResponse
    {
        $ad = Ad::with(['category', 'subcategory', 'seller.region', 'seller.city', ...self::AD_DETAIL_RELATIONS])
            ->where('id', $id)
            ->where('status', 'active')
            ->first();

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi yoki faol emas.', 404);
        }

        return response()->successJson($ad);
    }
}
