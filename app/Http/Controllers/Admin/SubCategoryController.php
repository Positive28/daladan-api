<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            ->with(['category:id,name,slug', 'parent:id,name,slug'])
            ->withCount('children')
            ->when(
                $request->filled('category_id'),
                fn ($query) => $query->where('category_id', $request->input('category_id'))
            )
            ->when(
                $request->filled('parent_id'),
                fn ($query) => $query->where('parent_id', $request->input('parent_id'))
            )
            ->when(
                $request->boolean('roots_only'),
                fn ($query) => $query->whereNull('parent_id')
            )
            ->when(
                $request->filled('is_active'),
                fn ($query) => $query->where('is_active', (bool) $request->input('is_active'))
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->input('per_page', 15));

        return response()->successJson($subcategories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'parent_id'   => 'nullable|integer|exists:subcategories,id',
            'name'        => 'required|string|max:80',
            'slug'        => 'required|string|max:80|unique:subcategories,slug',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'required|boolean',
            'icon'        => 'nullable|file|mimes:svg|max:512',
        ]);

        $this->assertValidParent(
            categoryId: (int) $validated['category_id'],
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
        );

        unset($validated['icon']);
        $subcategory = Subcategory::create($validated);
        if ($request->hasFile('icon')) {
            $subcategory->addMediaFromRequest('icon')->toMediaCollection('icon');
        }
        $subcategory->load(['category:id,name,slug', 'parent:id,name,slug']);
        $subcategory->loadCount('children');

        return response()->successJson($subcategory, 201);
    }

    public function show(Subcategory $subcategory): JsonResponse
    {
        $subcategory->load(['category:id,name,slug', 'parent:id,name,slug']);
        $subcategory->loadCount('children');

        return response()->successJson($subcategory);
    }

    public function update(Request $request, Subcategory $subcategory): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'parent_id'   => 'nullable|integer|exists:subcategories,id',
            'name'        => 'sometimes|required|string|max:80',
            'slug'        => 'sometimes|required|string|max:80|unique:subcategories,slug,' . $subcategory->id,
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'sometimes|required|boolean',
            'icon'        => 'nullable|file|mimes:svg|max:512',
        ]);

        $categoryId = (int) ($validated['category_id'] ?? $subcategory->category_id);
        $parentId = array_key_exists('parent_id', $validated)
            ? ($validated['parent_id'] !== null ? (int) $validated['parent_id'] : null)
            : $subcategory->parent_id;

        $this->assertValidParent(
            categoryId: $categoryId,
            parentId: $parentId,
            current: $subcategory,
        );

        unset($validated['icon']);
        $subcategory->update($validated);
        if ($request->hasFile('icon')) {
            $subcategory->addMediaFromRequest('icon')->toMediaCollection('icon');
        }
        $subcategory->load(['category:id,name,slug', 'parent:id,name,slug']);
        $subcategory->loadCount('children');

        return response()->successJson($subcategory);
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        if ($subcategory->children()->exists()) {
            return response()->errorJson('Avval ichki subkategoriyalarni o\'chiring.', 422);
        }

        if ($subcategory->ads()->exists()) {
            return response()->errorJson('Bu subkategoriyada e\'lonlar bor.', 422);
        }

        $subcategory->delete();

        return response()->successJson(['message' => 'Subcategory o\'chirildi.']);
    }

    private function assertValidParent(int $categoryId, ?int $parentId, ?Subcategory $current = null): void
    {
        if ($parentId === null) {
            return;
        }

        if ($current !== null && $current->wouldCreateCycle($parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Subkategoriya o\'zining bolasi yoki o\'ziga bog\'lanishi mumkin emas.',
            ]);
        }

        $parent = Subcategory::query()->find($parentId);
        if ($parent === null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Ota subkategoriya topilmadi.',
            ]);
        }

        if ((int) $parent->category_id !== $categoryId) {
            throw ValidationException::withMessages([
                'parent_id' => 'Ota subkategoriya tanlangan kategoriyaga tegishli emas.',
            ]);
        }
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
     *         name="parent_id",
     *         in="query",
     *         required=false,
     *         description="Faqat shu ota subcategory ostidagi bolalar",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="roots_only",
     *         in="query",
     *         required=false,
     *         description="Faqat root (parent_id=null) subcategorylar",
     *         @OA\Schema(type="boolean", example=true)
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
     *     description="parent_id berilmasa — 2-daraja (root). parent_id berilsa — ichki subkategoriya (3+ daraja). parent_id shu category_id ga tegishli bo'lishi kerak.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/AdminSubcategoryPayload")
     *         )
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
     *     description="parent_id o'zgartirish mumkin. Tsikl (o'ziga yoki bolasiga bog'lash) taqiqlanadi.",
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/AdminSubcategoryPayload")
     *         )
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
     *     @OA\Property(property="parent_id", type="integer", nullable=true, example=5),
     *     @OA\Property(property="name", type="string", example="Echkilar"),
     *     @OA\Property(property="slug", type="string", example="echkilar"),
     *     @OA\Property(property="sort_order", type="integer", nullable=true, example=1),
     *     @OA\Property(property="is_active", type="boolean", example=true),
     *     @OA\Property(property="icon_url", type="string", nullable=true, example="http://localhost/storage/2/subcategory-icon.svg"),
     *     @OA\Property(property="children_count", type="integer", example=3),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-18T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-18T10:00:00Z"),
     *     @OA\Property(
     *         property="category",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=4),
     *         @OA\Property(property="name", type="string", example="Chorva hayvonlari"),
     *         @OA\Property(property="slug", type="string", example="chorva-hayvonlari")
     *     ),
     *     @OA\Property(
     *         property="parent",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=5),
     *         @OA\Property(property="name", type="string", example="Qo'y va echkilar"),
     *         @OA\Property(property="slug", type="string", example="qoy-va-echkilar")
     *     )
     * )
     * @OA\Schema(
     *     schema="AdminSubcategoryPayload",
     *     type="object",
     *     required={"category_id","name","slug","is_active"},
     *     @OA\Property(property="category_id", type="integer", example=4),
     *     @OA\Property(property="parent_id", type="integer", nullable=true, example=5),
     *     @OA\Property(property="name", type="string", maxLength=80, example="Echkilar"),
     *     @OA\Property(property="slug", type="string", maxLength=80, example="echkilar"),
     *     @OA\Property(property="sort_order", type="integer", nullable=true, example=1),
     *     @OA\Property(property="is_active", type="boolean", example=true),
     *     @OA\Property(property="icon", type="string", format="binary", nullable=true, description="SVG icon fayl")
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
