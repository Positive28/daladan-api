<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Category;
use App\Models\Region;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Resources",
 *     description="Viloyat va tumanlar resurslari"
 * )
 */
class ResourceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/resources/regions",
     *     tags={"Resources"},
     *     summary="Faol viloyatlar ro'yxati (ichida faol tumanlari bilan)",
     *     @OA\Response(
     *         response=200,
     *         description="Viloyatlar ro'yxati"
     *     )
     * )
     */
    public function regions(): JsonResponse
    {
        $regions = Region::where('is_active', true)
            ->with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get(['id', 'name_uz', 'slug']);

        return response()->json($regions);
    }

    /**
     * @OA\Get(
     *     path="/resources/cities",
     *     tags={"Resources"},
     *     summary="Faol tumanlar ro'yxati (ixtiyoriy region_id bo'yicha)",
     *     @OA\Parameter(
     *         name="region_id",
     *         in="query",
     *         required=false,
     *         description="Faqat shu viloyatga tegishli tumanlarni olish uchun",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tumanlar ro'yxati"
     *     )
     * )
     */
    public function cities(Request $request): JsonResponse
    {
        $query = City::where('is_active', true)->orderBy('sort_order');

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $cities = $query->get(['id', 'region_id', 'name_uz', 'slug']);

        return response()->json($cities);
    }

    /**
     * @OA\Get(
     *     path="/resources/categories",
     *     tags={"Resources"},
     *     summary="Faol kategoriyalar ro'yxati",
     *     @OA\Response(
     *         response=200,
     *         description="Kategoriyalar ro'yxati"
     *     )
     * )
     */
    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        return response()->json($categories);
    }

    /**
     * @OA\Get(
     *     path="/resources/subcategories",
     *     tags={"Resources"},
     *     summary="Faol subkategoriyalar ro'yxati (category_id bo'yicha filter)",
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         description="Faqat shu kategoriyaga tegishli subkategoriyalar uchun",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subkategoriyalar ro'yxati"
     *     )
     * )
     */
    public function subcategories(Request $request): JsonResponse
    {
        $query = Subcategory::where('is_active', true)->orderBy('sort_order');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $subcategories = $query->get(['id', 'category_id', 'name', 'slug']);

        return response()->json($subcategories);
    }
}
