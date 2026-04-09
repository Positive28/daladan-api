<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class SubCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'admin']);
    }

    public function index(Request $request): JsonResponse
    {
        $subcategories = Subcategory::query()
            ->with(['category:id,name,slug'])
            ->when(
                $request->filled('category_id'),
                fn ($query) => $query->where('category_id', $request->input('category_id'))
            )
            ->when(
                $request->filled('is_active'),
                fn ($query) => $query->where('is_active', (bool) $request->input('is_active'))
            )
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return response()->json(response()->successJson($subcategories));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:80',
            'slug' => 'required|string|max:80|unique:subcategories,slug',
            'sort_order' => 'nullable|integer',
            'is_active' => 'required|boolean',
        ]);

        $subcategory = Subcategory::create($validated);
        $subcategory->load('category:id,name,slug');

        return response()->json(response()->successJson($subcategory), 201);
    }

    public function show(Subcategory $subcategory): JsonResponse
    {
        $subcategory->load('category:id,name,slug');
        return response()->json(response()->successJson($subcategory));
    }

    public function update(Request $request, Subcategory $subcategory): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'name' => 'sometimes|required|string|max:80',
            'slug' => 'sometimes|required|string|max:80|unique:subcategories,slug,' . $subcategory->id,
            'sort_order' => 'nullable|integer',
            'is_active' => 'sometimes|required|boolean',
        ]);

        $subcategory->update($validated);
        $subcategory->load('category:id,name,slug');

        return response()->json(response()->successJson($subcategory));
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        $subcategory->delete();

        return response()->json(response()->successJson(['message' => 'Subcategory o\'chirildi.']));
    }

    /**
     * index() — GET /admin/subcategories
     * @OA\Get(
     *     path="/admin/subcategories",
     *     tags={"Admin Subcategories"},
     *     summary="Subcategorylar ro'yxati",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         required=false,
     *         description="Faqat shu categoryga tegishli subcategorylar",
     *         @OA\Schema(type="integer", example=1)
     *     ),
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
     *         description="Subcategorylar ro'yxati",
     *         @OA\JsonContent(ref="#/components/schemas/AdminSubcategoryListResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerIndex(): void {}

    /**
     * store() — POST /admin/subcategories
     * @OA\Post(
     *     path="/admin/subcategories",
     *     tags={"Admin Subcategories"},
     *     summary="Categoryga bog'langan subcategory yaratish",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AdminSubcategoryPayload")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subcategory yaratildi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminSubcategoryResponse")
     *     ),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerStore(): void {}

    /**
     * show() — GET /admin/subcategories/{subcategory}
     * @OA\Get(
     *     path="/admin/subcategories/{subcategory}",
     *     tags={"Admin Subcategories"},
     *     summary="Bitta subcategory ma'lumotini olish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subcategory",
     *         in="path",
     *         required=true,
     *         description="Subcategory ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subcategory topildi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminSubcategoryResponse")
     *     ),
     *     @OA\Response(response=404, description="Subcategory topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerShow(): void {}

    /**
     * update() — PUT /admin/subcategories/{subcategory}
     * @OA\Put(
     *     path="/admin/subcategories/{subcategory}",
     *     tags={"Admin Subcategories"},
     *     summary="Subcategory yangilash",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subcategory",
     *         in="path",
     *         required=true,
     *         description="Subcategory ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AdminSubcategoryPayload")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subcategory yangilandi",
     *         @OA\JsonContent(ref="#/components/schemas/AdminSubcategoryResponse")
     *     ),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=404, description="Subcategory topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerUpdate(): void {}

    /**
     * destroy() — DELETE /admin/subcategories/{subcategory}
     * @OA\Delete(
     *     path="/admin/subcategories/{subcategory}",
     *     tags={"Admin Subcategories"},
     *     summary="Subcategory o'chirish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subcategory",
     *         in="path",
     *         required=true,
     *         description="Subcategory ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subcategory o'chirildi",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="ok"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Subcategory o'chirildi.")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Subcategory topilmadi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerDestroy(): void {}

    /**
     * @OA\Tag(
     *     name="Admin Subcategories",
     *     description="Categoryga bog'langan subcategory CRUD endpointlari"
     * )
     * @OA\Schema(
     *     schema="AdminSubcategory",
     *     type="object",
     *     required={"id","category_id","name","slug","is_active","created_at","updated_at"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="category_id", type="integer", example=4),
     *     @OA\Property(property="name", type="string", example="Echkilar"),
     *     @OA\Property(property="slug", type="string", example="echkilar"),
     *     @OA\Property(property="sort_order", type="integer", nullable=true, example=1),
     *     @OA\Property(property="is_active", type="boolean", example=true),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-18T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-18T10:00:00Z"),
     *     @OA\Property(
     *         property="category",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=4),
     *         @OA\Property(property="name", type="string", example="Chorva hayvonlari"),
     *         @OA\Property(property="slug", type="string", example="chorva-hayvonlari")
     *     )
     * )
     * @OA\Schema(
     *     schema="AdminSubcategoryPayload",
     *     type="object",
     *     required={"category_id","name","slug","is_active"},
     *     @OA\Property(property="category_id", type="integer", example=4),
     *     @OA\Property(property="name", type="string", maxLength=80, example="Echkilar"),
     *     @OA\Property(property="slug", type="string", maxLength=80, example="echkilar"),
     *     @OA\Property(property="sort_order", type="integer", nullable=true, example=1),
     *     @OA\Property(property="is_active", type="boolean", example=true)
     * )
     * @OA\Schema(
     *     schema="AdminSubcategoryResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(property="data", ref="#/components/schemas/AdminSubcategory")
     * )
     * @OA\Schema(
     *     schema="AdminSubcategoryListResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=42),
     *         @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/AdminSubcategory")
     *         )
     *     )
     * )
     */
    private function _swaggerSchemas(): void {}
}
