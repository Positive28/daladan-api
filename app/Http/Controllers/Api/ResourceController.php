<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Category;
use App\Models\Region;
use App\Models\PromotionPlan;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ResourceController extends Controller
{
    public function regions(): JsonResponse
    {
        $regions = Region::where('is_active', true)
            ->with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get(['id', 'name_uz', 'slug']);

        return response()->json($regions);
    }

    public function cities(Request $request): JsonResponse
    {
        $query = City::where('is_active', true)->orderBy('sort_order');

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        return response()->json($query->get(['id', 'region_id', 'name_uz', 'slug']));
    }

    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        return response()->json($categories);
    }

    public function subcategories(Request $request): JsonResponse
    {
        $query = Subcategory::where('is_active', true)->orderBy('sort_order');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json($query->get(['id', 'category_id', 'name', 'slug']));
    }

    /**
     * Tokensiz: sotuvchi forma uchun tariflar (narx, muddat, top_sale / boost).
     * Buyurtma: POST /profile/ads/{ad}/promotions + promotion_plan_id.
     */
    public function promotionPlans(): JsonResponse
    {
        $plans = PromotionPlan::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'price',
                'currency',
                'duration_days',
                'type',
                'sort_order',
            ]);

        return response()->successJson($plans);
    }

    // =========================================================================
    // Swagger / OpenAPI annotations
    // =========================================================================

    /**
     * regions() — GET /resources/regions
     * @OA\Get(
     *     path="/resources/regions",
     *     tags={"Resources"},
     *     summary="Faol viloyatlar ro'yxati (ichida faol tumanlari bilan)",
     *     @OA\Response(
     *         response=200,
     *         description="Viloyatlar ro'yxati",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ResourceRegion"))
     *     )
     * )
     */
    private function _swaggerRegions(): void {}

    /**
     * cities() — GET /resources/cities
     * @OA\Get(
     *     path="/resources/cities",
     *     tags={"Resources"},
     *     summary="Faol tumanlar ro'yxati (ixtiyoriy region_id bo'yicha)",
     *     @OA\Parameter(name="region_id", in="query", required=false,
     *         description="Faqat shu viloyatga tegishli tumanlar",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tumanlar ro'yxati",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ResourceCity"))
     *     )
     * )
     */
    private function _swaggerCities(): void {}

    /**
     * categories() — GET /resources/categories
     * @OA\Get(
     *     path="/resources/categories",
     *     tags={"Resources"},
     *     summary="Faol kategoriyalar ro'yxati",
     *     @OA\Response(
     *         response=200,
     *         description="Kategoriyalar ro'yxati",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ResourceCategory"))
     *     )
     * )
     */
    private function _swaggerCategories(): void {}

    /**
     * subcategories() — GET /resources/subcategories
     * @OA\Get(
     *     path="/resources/subcategories",
     *     tags={"Resources"},
     *     summary="Faol subkategoriyalar ro'yxati (ixtiyoriy category_id bo'yicha)",
     *     @OA\Parameter(name="category_id", in="query", required=false,
     *         description="Faqat shu kategoriyaga tegishli subkategoriyalar",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subkategoriyalar ro'yxati",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ResourceSubcategory"))
     *     )
     * )
     */
    private function _swaggerSubcategories(): void {}

    /**
     * promotionPlans() — GET /resources/promotion-plans
     * @OA\Get(
     *     path="/resources/promotion-plans",
     *     tags={"Resources"},
     *     summary="Promo tariflar katalogi (tokensiz)",
     *     description="promotion_plans jadvalidan faol qatorlar — keyin POST /profile/ads/{ad}/promotions uchun promotion_plan_id",
     *     @OA\Response(
     *         response=200,
     *         description="Tariflar ro'yxati",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="ok"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ResourcePromotionPlan"))
     *         )
     *     )
     * )
     */
    private function _swaggerPromotionPlans(): void {}

    /**
     * @OA\Tag(
     *     name="Resources",
     *     description="Viloyat, tuman, kategoriya, subkategoriya va promo tariflari"
     * )
     * @OA\Schema(
     *     schema="ResourceCity",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=10),
     *     @OA\Property(property="region_id", type="integer", example=1),
     *     @OA\Property(property="name_uz", type="string", example="Qorovulbozor"),
     *     @OA\Property(property="slug", type="string", example="qorovulbozor")
     * )
     * @OA\Schema(
     *     schema="ResourceRegion",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name_uz", type="string", example="Buxoro"),
     *     @OA\Property(property="slug", type="string", example="buxoro"),
     *     @OA\Property(property="cities", type="array", @OA\Items(ref="#/components/schemas/ResourceCity"))
     * )
     * @OA\Schema(
     *     schema="ResourceCategory",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=4),
     *     @OA\Property(property="name", type="string", example="Chorva hayvonlari"),
     *     @OA\Property(property="slug", type="string", example="chorva-hayvonlari")
     * )
     * @OA\Schema(
     *     schema="ResourceSubcategory",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=11),
     *     @OA\Property(property="category_id", type="integer", example=4),
     *     @OA\Property(property="name", type="string", example="Echkilar"),
     *     @OA\Property(property="slug", type="string", example="echkilar")
     * )
     * @OA\Schema(
     *     schema="ResourcePromotionPlan",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Top sotuv — 7 kun"),
     *     @OA\Property(property="slug", type="string", example="top-sale-7d"),
     *     @OA\Property(property="description", type="string", nullable=true),
     *     @OA\Property(property="price", type="number", format="float", example=9000),
     *     @OA\Property(property="currency", type="string", example="UZS"),
     *     @OA\Property(property="duration_days", type="integer", example=7),
     *     @OA\Property(property="type", type="string", enum={"top_sale","boost"}, example="top_sale"),
     *     @OA\Property(property="sort_order", type="integer", example=1)
     * )
     */
    private function _swaggerSchemas(): void {}
}
