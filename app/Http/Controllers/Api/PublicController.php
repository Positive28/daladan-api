<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Services\AdViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PublicController extends Controller
{
    public function __construct(
        private readonly AdViewService $viewService
    ) {}

    public function ads(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'    => 'sometimes|nullable|integer|exists:categories,id',
            'subcategory_id' => 'sometimes|nullable|integer|exists:subcategories,id',
        ]);

        $query = Ad::query()
            ->where('status', 'active')
            ->with(['category', 'subcategory', 'seller.region', 'seller.city']);

        if (!empty($validated['category_id'] ?? null)) {
            $query->where('category_id', $validated['category_id']);
        }
        if (!empty($validated['subcategory_id'] ?? null)) {
            $query->where('subcategory_id', $validated['subcategory_id']);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        // Avval jonli boost, keyin top (ads ustunlari bo'yicha), keyin yangi e'lonlar.
        $ads = $query
            ->orderByLiveHighlight()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->successJson($ads);
    }

    public function ad(Request $request, string $id): JsonResponse
    {
        $ad = Ad::with(['category:id,name', 'subcategory:id,name', 'seller.region', 'seller.city'])
            ->where('id', $id)
            ->where('status', 'active')
            ->first();

        if (!$ad) {
            return response()->errorJson('E\'lon topilmadi yoki faol emas.', 404);
        }

        $this->viewService->record($ad, $request);
        $ad->refresh();

        return response()->successJson($ad);
    }

    // =========================================================================
    // Swagger / OpenAPI annotations
    // =========================================================================

    /**
     * ads() — GET /public/ads
     * @OA\Get(
     *     path="/public/ads",
     *     tags={"Public"},
     *     summary="Barcha faol e'lonlar (ixtiyoriy filter)",
     *     description="category_id/subcategory_id yuborilmasa — barcha faol e'lonlar; yuborilsa — shu bo'yicha filter.",
     *     @OA\Parameter(name="per_page",       in="query", required=false, description="1–50, default 15", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="category_id",    in="query", required=false, description="Berilmasa barcha kategoriyalar", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subcategory_id", in="query", required=false, description="Berilmasa filter yo'q", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginatsiyali e'lonlar",
     *         @OA\JsonContent(ref="#/components/schemas/PublicAdListSuccessResponse")
     *     ),
     *     @OA\Response(response=422, description="category_id/subcategory_id noto'g'ri")
     * )
     */
    private function _swaggerAds(): void {}

    /**
     * ad() — GET /public/ads/{id}
     * @OA\Get(
     *     path="/public/ads/{id}",
     *     tags={"Public"},
     *     summary="Bitta faol e'lon",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="E'lon obyekti",
     *         @OA\JsonContent(ref="#/components/schemas/PublicAdSuccessResponse")
     *     ),
     *     @OA\Response(response=404, description="Topilmadi yoki faol emas")
     * )
     */
    private function _swaggerAd(): void {}

    /**
     * @OA\Tag(
     *     name="Public",
     *     description="Front uchun ochiq (tokensiz) endpointlar"
     * )
     * @OA\Schema(
     *     schema="PublicAd",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=201),
     *     @OA\Property(property="category_id", type="integer", example=4),
     *     @OA\Property(property="subcategory_id", type="integer", example=11),
     *     @OA\Property(property="seller_id", type="integer", example=1),
     *     @OA\Property(property="title", type="string", example="Naslli echkilar"),
     *     @OA\Property(property="description", type="string", nullable=true),
     *     @OA\Property(property="district", type="string", nullable=true),
     *     @OA\Property(property="price", type="integer", nullable=true, example=2200000),
     *     @OA\Property(property="quantity", type="number", format="float", nullable=true, example=12),
     *     @OA\Property(property="unit", type="string", nullable=true, example="piece"),
     *     @OA\Property(property="status", type="string", example="active"),
     *     @OA\Property(property="is_top_sale", type="boolean", example=false),
     *     @OA\Property(property="is_boosted", type="boolean", example=false),
     *     @OA\Property(property="boost_starts_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="boost_expires_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="highlight_active", type="boolean", example=false),
     *     @OA\Property(property="created_at", type="string", format="date-time")
     * )
     * @OA\Schema(
     *     schema="PublicAdSuccessResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(property="data", ref="#/components/schemas/PublicAd")
     * )
     * @OA\Schema(
     *     schema="PublicAdListSuccessResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=300),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PublicAd"))
     *     )
     * )
     */
    private function _swaggerSchemas(): void {}
}
