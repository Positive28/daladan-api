<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Animal;
use App\Models\Category;
use App\Models\Forage;
use App\Models\Fruit;
use App\Models\Grain;
use App\Models\Poultry;
use App\Models\Vegetable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * Foydalanuvchining o'z reklamalari CRUD — barcha yo'llar /api/v1/profile/ads (JWT).
 */
class AdsController extends Controller
{
    private const DETAIL_RELATIONS = ['animal', 'poultry', 'grain', 'fruit', 'forage', 'vegetable'];

    /**
     * @OA\Get(
     *     path="/profile/ads",
     *     tags={"Profile","Ads"},
     *     summary="O'z reklamalari ro'yxati",
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
            ->with(['category', 'subcategory', 'seller.region', 'seller.city', ...self::DETAIL_RELATIONS])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->successJson($ads);
    }

    /**
     * @OA\Post(
     *     path="/profile/ads",
     *     tags={"Profile","Ads"},
     *     summary="Yangi reklama yaratish",
     *     description="ads jadvaliga category_id/subcategory_id/district yoziladi; kategoriya slug bo'yicha detail animals/poultries/... jadvaliga title va boshqalar ketadi.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"category_id","subcategory_id"},
     *                 @OA\Property(property="category_id", type="integer", description="Kategoriya ID", example=1),
     *                 @OA\Property(property="subcategory_id", type="integer", description="Shu kategoriyaga tegishli subkategoriya ID", example=1),
     *                 @OA\Property(property="district", type="string", nullable=true, maxLength=100),
     *                 @OA\Property(property="title", type="string", description="chorva, parranda, don, meva, yem, sabzavot — detail uchun", example="Qo'y sotiladi"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="price", type="number", description="chorva (nullable), don, meva, yem, sabzavot — detail"),
     *                 @OA\Property(property="quantity", type="number", description="meva, sabzavot"),
     *                 @OA\Property(property="unit", type="string", description="don: kg|ton|bag; meva/sabzavot: kg|ton|box|piece", example="kg"),
     *                 @OA\Property(property="poultry_type", type="string", maxLength=100),
     *                 @OA\Property(property="breed", type="string", nullable=true, maxLength=100),
     *                 @OA\Property(property="price_per_head", type="number", description="parranda"),
     *                 @OA\Property(property="grain_type", type="string", nullable=true, maxLength=100),
     *                 @OA\Property(property="variety", type="string", nullable=true, maxLength=100),
     *                 @OA\Property(property="forage_type", type="string", nullable=true, maxLength=100),
     *                 @OA\Property(property="vegetable_type", type="string", maxLength=100),
     *                 @OA\Property(property="is_negotiable", type="boolean", description="meva"),
     *                 @OA\Property(
     *                     property="media[]",
     *                     type="array",
     *                     description="Rasm/video fayllar",
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
            'subcategory_id'      => [
                'required',
                'integer',
                Rule::exists('subcategories', 'id')->where(fn ($q) => $q->where('category_id', $request->input('category_id'))),
            ],
            'district'            => 'nullable|string|max:100',
            'media'               => 'nullable|array',
            'media.*'             => 'file|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime|max:51200',
        ], [
            'subcategory_id.exists' => 'Subkategoriya tanlangan kategoriyaga tegishli emas.',
        ]);

        $slug = Category::query()->whereKey($validated['category_id'])->value('slug');
        if (!$slug) {
            return response()->errorJson('Kategoriya topilmadi.', 422);
        }

        $detailValidated = $request->validate($this->detailRulesBySlug($slug, false));

        $validated['seller_id'] = $user->id;
        $validated['region_id'] = $user->region_id;
        $validated['city_id'] = $user->city_id;
        $validated['status'] = 'active';
        unset($validated['media']);

        $ad = Ad::create($validated);
        $this->syncDetailByCategory($ad, $slug, $detailValidated);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $ad->addMedia($file)->toMediaCollection('gallery');
            }
        }

        $ad->load(['category', 'subcategory', 'seller.region', 'seller.city', ...self::DETAIL_RELATIONS]);

        return response()->json($ad, 201);
    }

    /**
     * @OA\Get(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="Bitta o'z reklamasini olish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ad",
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
    public function show(Request $request, string $ad): JsonResponse
    {
        $model = Ad::with(['category', 'subcategory', 'seller.region', 'seller.city', ...self::DETAIL_RELATIONS])
            ->where('id', $ad)
            ->where('seller_id', $request->user()->id)
            ->first();

        if (!$model) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        return response()->successJson($model);
    }

    /**
     * @OA\Post(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="Reklamani yangilash (form-data, _method=PUT)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ad",
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
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="price", type="number"),
     *                 @OA\Property(property="quantity", type="number"),
     *                 @OA\Property(property="unit", type="string"),
     *                 @OA\Property(property="poultry_type", type="string"),
     *                 @OA\Property(property="breed", type="string", nullable=true),
     *                 @OA\Property(property="price_per_head", type="number"),
     *                 @OA\Property(property="grain_type", type="string", nullable=true),
     *                 @OA\Property(property="variety", type="string", nullable=true),
     *                 @OA\Property(property="forage_type", type="string", nullable=true),
     *                 @OA\Property(property="vegetable_type", type="string"),
     *                 @OA\Property(property="is_negotiable", type="boolean"),
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
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="Reklamani yangilash (JSON yoki form-data)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ad",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="subcategory_id", type="integer"),
     *             @OA\Property(property="district", type="string", nullable=true),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="quantity", type="number"),
     *             @OA\Property(property="unit", type="string"),
     *             @OA\Property(property="poultry_type", type="string"),
     *             @OA\Property(property="breed", type="string", nullable=true),
     *             @OA\Property(property="price_per_head", type="number"),
     *             @OA\Property(property="grain_type", type="string", nullable=true),
     *             @OA\Property(property="variety", type="string", nullable=true),
     *             @OA\Property(property="forage_type", type="string", nullable=true),
     *             @OA\Property(property="vegetable_type", type="string"),
     *             @OA\Property(property="is_negotiable", type="boolean"),
     *             @OA\Property(property="status", type="string", enum={"active","sold","deleted"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="E'lon yangilandi"),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     *
     * @OA\Patch(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="Reklamani qisman yangilash (PATCH)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ad",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="subcategory_id", type="integer"),
     *             @OA\Property(property="district", type="string", nullable=true),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="quantity", type="number"),
     *             @OA\Property(property="unit", type="string"),
     *             @OA\Property(property="poultry_type", type="string"),
     *             @OA\Property(property="breed", type="string", nullable=true),
     *             @OA\Property(property="price_per_head", type="number"),
     *             @OA\Property(property="grain_type", type="string", nullable=true),
     *             @OA\Property(property="variety", type="string", nullable=true),
     *             @OA\Property(property="forage_type", type="string", nullable=true),
     *             @OA\Property(property="vegetable_type", type="string"),
     *             @OA\Property(property="is_negotiable", type="boolean"),
     *             @OA\Property(property="status", type="string", enum={"active","sold","deleted"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="E'lon yangilandi"),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=422, description="Validatsiya xatosi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, string $ad): JsonResponse
    {
        $record = Ad::where('id', $ad)->where('seller_id', $request->user()->id)->first();

        if (!$record) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        $rules = [
            'category_id'          => 'sometimes|integer|exists:categories,id',
            'subcategory_id'      => 'sometimes|integer',
            'district'            => 'nullable|string|max:100',
            'status'              => 'sometimes|string|in:active,sold,deleted',
            'media'               => 'nullable|array',
            'media.*'             => 'file|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime|max:51200',
            'delete_media_ids'    => 'nullable|array',
            'delete_media_ids.*'  => 'integer|exists:media,id',
        ];

        if ($request->filled('category_id') && ! $request->filled('subcategory_id')) {
            $rules['subcategory_id'] = ['required', 'integer'];
        }

        $categoryIdForSub = $request->input('category_id', $record->category_id);
        if ($request->filled('subcategory_id')) {
            $rules['subcategory_id'][] = Rule::exists('subcategories', 'id')
                ->where(fn ($q) => $q->where('category_id', $categoryIdForSub));
        }

        $validated = $request->validate($rules, [
            'subcategory_id.exists' => 'Subkategoriya tanlangan kategoriyaga tegishli emas.',
            'subcategory_id.required' => 'Kategoriya o‘zgartirilsa, subkategoriya ham yuborilishi kerak.',
        ]);

        $adPayload = $validated;
        unset($adPayload['media'], $adPayload['delete_media_ids']);
        $record->update($adPayload);

        $resolvedCategoryId = (int) ($validated['category_id'] ?? $record->category_id);
        $slug = Category::query()->whereKey($resolvedCategoryId)->value('slug');

        if (!$slug) {
            return response()->errorJson('Kategoriya topilmadi.', 422);
        }

        $detailFieldNames = $this->detailFieldNames();
        $hasDetailInput = count(array_intersect(array_keys($request->all()), $detailFieldNames)) > 0;
        $isCategoryChanged = array_key_exists('category_id', $validated);

        if ($isCategoryChanged || $hasDetailInput) {
            $detailValidated = $request->validate(
                $this->detailRulesBySlug($slug, ! $isCategoryChanged)
            );
            $this->syncDetailByCategory($record->fresh(), $slug, $detailValidated);
        }

        if ($request->filled('delete_media_ids')) {
            foreach ($request->delete_media_ids as $mediaId) {
                $record->getMedia('gallery')->where('id', $mediaId)->first()?->delete();
            }
        }

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $record->addMedia($file)->toMediaCollection('gallery');
            }
        }

        return response()->successJson($record->fresh(['category', 'subcategory', 'seller.region', 'seller.city', ...self::DETAIL_RELATIONS]));
    }

    /**
     * @OA\Delete(
     *     path="/profile/ads/{ad}",
     *     tags={"Profile","Ads"},
     *     summary="O'z reklamasini o'chirish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ad",
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
    public function destroy(Request $request, string $ad): JsonResponse
    {
        $record = Ad::where('id', $ad)->where('seller_id', $request->user()->id)->first();

        if (!$record) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        $record->delete();

        return response()->successJson(['message' => 'E\'lon o\'chirildi.']);
    }

    private function syncDetailByCategory(Ad $ad, string $slug, array $detailData): void
    {
        $ad->loadMissing('subcategory');

        $mapBySlug = [
            'chorva-hayvonlari' => Animal::class,
            'parranda' => Poultry::class,
            'don' => Grain::class,
            'meva' => Fruit::class,
            'yem-ozuqa' => Forage::class,
            'sabzavot' => Vegetable::class,
        ];

        $targetModel = $mapBySlug[$slug] ?? null;

        $fallbackType = (string) ($ad->subcategory?->name ?? 'Umumiy');
        $detailPayloadByModel = [
            Animal::class => [
                'title' => $detailData['title'] ?? '',
                'description' => $detailData['description'] ?? null,
                'price' => $detailData['price'] ?? null,
            ],
            Poultry::class => [
                'title' => $detailData['title'] ?? '',
                'description' => $detailData['description'] ?? null,
                'poultry_type' => $detailData['poultry_type'] ?? $fallbackType,
                'breed' => $detailData['breed'] ?? null,
                'price_per_head' => (int) round((float) ($detailData['price_per_head'] ?? 0)),
            ],
            Grain::class => [
                'title' => $detailData['title'] ?? '',
                'description' => $detailData['description'] ?? null,
                'grain_type' => $detailData['grain_type'] ?? $fallbackType,
                'variety' => $detailData['variety'] ?? null,
                'unit' => $detailData['unit'] ?? 'ton',
                'price' => (int) round((float) ($detailData['price'] ?? 0)),
            ],
            Fruit::class => [
                'title' => $detailData['title'] ?? '',
                'description' => $detailData['description'] ?? null,
                'quantity' => $detailData['quantity'] ?? 0,
                'unit' => $detailData['unit'] ?? 'kg',
                'price' => (int) round((float) ($detailData['price'] ?? 0)),
                'is_negotiable' => $detailData['is_negotiable'] ?? false,
            ],
            Forage::class => [
                'title' => $detailData['title'] ?? '',
                'description' => $detailData['description'] ?? null,
                'forage_type' => $detailData['forage_type'] ?? $fallbackType,
                'price' => (int) round((float) ($detailData['price'] ?? 0)),
            ],
            Vegetable::class => [
                'title' => $detailData['title'] ?? '',
                'description' => $detailData['description'] ?? null,
                'vegetable_type' => $detailData['vegetable_type'] ?? $fallbackType,
                'variety' => $detailData['variety'] ?? null,
                'quantity' => $detailData['quantity'] ?? 0,
                'unit' => $detailData['unit'] ?? 'kg',
                'price' => (int) round((float) ($detailData['price'] ?? 0)),
            ],
        ];

        foreach ($mapBySlug as $categorySlug => $modelClass) {
            if ($modelClass === $targetModel) {
                continue;
            }
            $modelClass::query()->where('ad_id', $ad->id)->delete();
        }

        if ($targetModel) {
            $targetModel::updateOrCreate(
                ['ad_id' => $ad->id],
                $detailPayloadByModel[$targetModel] ?? ['title' => $detailData['title'] ?? '', 'description' => $detailData['description'] ?? null]
            );
        }
    }

    private function detailFieldNames(): array
    {
        return [
            'title',
            'description',
            'price',
            'quantity',
            'unit',
            'poultry_type',
            'breed',
            'price_per_head',
            'grain_type',
            'variety',
            'is_negotiable',
            'forage_type',
            'vegetable_type',
        ];
    }

    private function detailRulesBySlug(string $slug, bool $isUpdate): array
    {
        $prefix = $isUpdate ? 'sometimes|' : 'required|';
        $titleRule = $isUpdate ? 'sometimes|string|max:150' : 'required|string|max:150';

        return match ($slug) {
            'chorva-hayvonlari' => [
                'title' => $titleRule,
                'description' => 'nullable|string',
                'price' => 'nullable|numeric|min:0',
            ],
            'parranda' => [
                'title' => $titleRule,
                'description' => 'nullable|string',
                'poultry_type' => $prefix . 'string|max:100',
                'breed' => 'nullable|string|max:100',
                'price_per_head' => $prefix . 'numeric|min:0',
            ],
            'don' => [
                'title' => $titleRule,
                'description' => 'nullable|string',
                'grain_type' => 'nullable|string|max:100',
                'variety' => 'nullable|string|max:100',
                'unit' => $prefix . 'in:kg,ton,bag',
                'price' => $prefix . 'numeric|min:0',
            ],
            'meva' => [
                'title' => $titleRule,
                'description' => 'nullable|string',
                'quantity' => $prefix . 'numeric|min:0',
                'unit' => $prefix . 'in:kg,ton,box,piece',
                'price' => $prefix . 'numeric|min:0',
                'is_negotiable' => 'sometimes|boolean',
            ],
            'yem-ozuqa' => [
                'title' => $titleRule,
                'description' => 'nullable|string',
                'forage_type' => 'nullable|string|max:100',
                'price' => $prefix . 'numeric|min:0',
            ],
            'sabzavot' => [
                'title' => $titleRule,
                'description' => 'nullable|string',
                'vegetable_type' => $prefix . 'string|max:100',
                'variety' => 'nullable|string|max:100',
                'quantity' => $prefix . 'numeric|min:0',
                'unit' => $prefix . 'in:kg,ton,box,piece',
                'price' => $prefix . 'numeric|min:0',
            ],
            default => [],
        };
    }
}
