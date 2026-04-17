<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'admin']);
    }

    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->when(
                $request->filled('is_active'),
                fn ($query) => $query->where('is_active', (bool) $request->input('is_active'))
            )
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return response()->json(response()->successJson($categories));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'required|string|max:80|unique:categories,slug',
            'sort_order' => 'nullable|integer',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        unset($validated['image']);
        $category = Category::create($validated);
        if ($request->hasFile('image')) {
            $category->addMediaFromRequest('image')->toMediaCollection('image');
            $category->refresh();
        }

        return response()->json(response()->successJson($category), 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(response()->successJson($category));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:80',
            'slug' => 'sometimes|required|string|max:80|unique:categories,slug,' . $category->id,
            'sort_order' => 'nullable|integer',
            'is_active' => 'sometimes|required|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        unset($validated['image']);
        $category->update($validated);
        if ($request->hasFile('image')) {
            $category->addMediaFromRequest('image')->toMediaCollection('image');
            $category->refresh();
        }

        return response()->json(response()->successJson($category));
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(response()->successJson(['message' => 'Category o\'chirildi.']));
    }

    /**
     * index() — GET /admin/categories
     * @OA\Get(
     *     path="/admin/categories",
     *     tags={"Admin Categories"},
     *     summary="Categorylar ro'yxati",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         required=false,
     *         description="Faol/NoFaol filter",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Sahifadagi elementlar soni",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categorylar ro'yxati",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCategoryListResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerIndex(): void {}

    /**
     * store() — POST /admin/categories
     * @OA\Post(
     *     path="/admin/categories",
     *     tags={"Admin Categories"},
     *     summary="Yangi category yaratish",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/AdminCategoryStorePayload")
 *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category yaratildi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCategoryResponse")
     *     ),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerStore(): void {}

    /**
     * show() — GET /admin/categories/{category}
     * @OA\Get(
     *     path="/admin/categories/{category}",
     *     tags={"Admin Categories"},
     *     summary="Bitta category ma'lumotini olish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category topildi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCategoryResponse")
     *     ),
     *     @OA\Response(response=404, description="Category topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerShow(): void {}

    /**
     * update() — PUT /admin/categories/{category}
     * @OA\Put(
     *     path="/admin/categories/{category}",
     *     tags={"Admin Categories"},
     *     summary="Category yangilash",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/AdminCategoryUpdatePayload")
 *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category yangilandi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminCategoryResponse")
     *     ),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=404, description="Category topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerUpdate(): void {}

    /**
     * destroy() — DELETE /admin/categories/{category}
     * @OA\Delete(
     *     path="/admin/categories/{category}",
     *     tags={"Admin Categories"},
     *     summary="Category o'chirish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category o'chirildi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="ok"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Category o'chirildi.")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Category topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerDestroy(): void {}

    /**
     * @OA\Tag(
     *     name="Admin Categories",
     *     description="Admin panel uchun category CRUD endpointlari"
     * )
     * @OA\Schema(
     *     schema="AdminCategory",
     *     type="object",
     *     required={"id","name","slug","is_active","created_at","updated_at"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Chorva hayvonlari"),
     *     @OA\Property(property="slug", type="string", example="chorva-hayvonlari"),
     *     @OA\Property(property="sort_order", type="integer", nullable=true, example=4),
     *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="http://localhost/storage/1/category-image.jpg"),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-18T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-18T10:00:00Z")
     * )
     * @OA\Schema(
 *     schema="AdminCategoryStorePayload",
     *     type="object",
     *     required={"name","slug","is_active"},
     *     @OA\Property(property="name", type="string", maxLength=80, example="Chorva hayvonlari"),
     *     @OA\Property(property="slug", type="string", maxLength=80, example="chorva-hayvonlari"),
     *     @OA\Property(property="sort_order", type="integer", nullable=true, example=4),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="image", type="string", format="binary", nullable=true)
 * )
 * @OA\Schema(
 *     schema="AdminCategoryUpdatePayload",
 *     type="object",
 *     @OA\Property(property="name", type="string", maxLength=80, example="Chorva hayvonlari"),
 *     @OA\Property(property="slug", type="string", maxLength=80, example="chorva-hayvonlari"),
 *     @OA\Property(property="sort_order", type="integer", nullable=true, example=4),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="image", type="string", format="binary", nullable=true)
     * )
     * @OA\Schema(
     *     schema="AdminCategoryResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(property="data", ref="#/components/schemas/AdminCategory")
     * )
     * @OA\Schema(
     *     schema="AdminCategoryListResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=23),
     *         @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/AdminCategory")
     *         )
     *     )
     * )
     */
    private function _swaggerSchemas(): void {}
}
