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
    /**
     * @OA\Get(
     *     path="/public/ads",
     *     tags={"Public"},
     *     summary="Barcha faol e'lonlar (ixtiyoriy filter)",
     *     description="category_id/subcategory_id yuborilmasa — barcha faol e'lonlar; yuborilsa — shu bo'yicha filter.",
     *     @OA\Parameter(name="per_page", in="query", required=false, description="1–50, default 15", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="category_id", in="query", required=false, description="Berilmasa barcha kategoriyalar", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subcategory_id", in="query", required=false, description="Berilmasa subkategoriya bo'yicha filter yo'q", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginatsiyali e'lonlar (category, subcategory, seller va boshqalar)"
     *     ),
     *     @OA\Response(response=422, description="category_id/subcategory_id noto'g'ri yoki bazada yo'q")
     * )
     */
    public function ads(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'subcategory_id' => 'sometimes|nullable|integer|exists:subcategories,id',
        ]);

        $query = Ad::query()
            ->where('status', 'active')
            ->with(['category', 'subcategory', 'seller.region', 'seller.city']);

        if (! empty($validated['category_id'] ?? null)) {
            $query->where('category_id', $validated['category_id']);
        }
        if (! empty($validated['subcategory_id'] ?? null)) {
            $query->where('subcategory_id', $validated['subcategory_id']);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $ads = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->publicSuccessJson($ads);
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
        $ad = Ad::with(['category', 'subcategory', 'seller.region', 'seller.city'])
            ->where('id', $id)
            ->where('status', 'active')
            ->first();

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi yoki faol emas.', 404);
        }

        return $this->publicSuccessJson($ad);
    }

    private function publicSuccessJson(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'ok',
        ]);
    }
}
