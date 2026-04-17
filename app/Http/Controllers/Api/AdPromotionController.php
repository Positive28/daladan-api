<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\PromotionPlan;
use App\Services\AdPromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * Sotuvchi promo buyurtmasi (pending). Keyingi qadam: to'lov + admin tasdiq.
 */
class AdPromotionController extends Controller
{
    public function __construct(
        private readonly AdPromotionService $promotionService
    ) {
        $this->middleware('auth:api');
    }

    /**
     * Faqat admin tasdig‘idan keyin (active) e’longa promo buyurtmasi — pending.
     */
    public function store(Request $request, string $ad): JsonResponse
    {
        $record = Ad::where('id', $ad)->where('seller_id', $request->user()->id)->first();

        if (!$record) {
            return response()->errorJson('E\'lon topilmadi.', 404);
        }

        // Faqat faol tariflar (GET /resources/promotion-plans dan kelgan id).
        $validated = $request->validate([
            'promotion_plan_id' => ['required', 'integer', Rule::exists('promotion_plans', 'id')->where(fn ($q) => $q->where('is_active', true))],
        ]);

        $plan = PromotionPlan::findOrFail($validated['promotion_plan_id']);

        try {
            $order = $this->promotionService->createPendingOrder($record, $request->user(), $plan);
        } catch (\InvalidArgumentException $e) {
            return response()->errorJson($e->getMessage(), 422);
        }

        $order->load('promotionPlan:id,name,slug,type,price,currency,duration_days');

        return response()->successJson($order, 201);
    }

    // =========================================================================
    // Swagger / OpenAPI annotations
    // =========================================================================

    /**
     * store() — POST /profile/ads/{ad}/promotions
     * @OA\Post(
     *     path="/profile/ads/{ad}/promotions",
     *     tags={"Profile","Ads"},
     *     summary="E'longa promo buyurtmasi (pending)",
     *     description="Faqat o'z e'loniga. Keyin to'lov + admin PATCH /admin/ad-promotions/{promotion}/confirm.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ad", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"promotion_plan_id"},
     *             @OA\Property(property="promotion_plan_id", type="integer", example=1, description="GET /resources/promotion-plans")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Buyurtma yaratildi", @OA\JsonContent(ref="#/components/schemas/AdPromotionStoreSuccessResponse")),
     *     @OA\Response(response=404, description="E'lon topilmadi"),
     *     @OA\Response(response=422, description="Validatsiya yoki biznes qoidasi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerStore(): void {}

    /**
     * @OA\Schema(
     *     schema="AdPromotionPlanSummary",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Top sotuv — 7 kun"),
     *     @OA\Property(property="slug", type="string", example="top-sale-7d"),
     *     @OA\Property(property="type", type="string", enum={"top_sale","boost"}),
     *     @OA\Property(property="price", type="number", format="float", example=9000),
     *     @OA\Property(property="currency", type="string", example="UZS"),
     *     @OA\Property(property="duration_days", type="integer", example=7)
     * )
     * @OA\Schema(
     *     schema="AdPromotionOrderItem",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="ad_id", type="integer", example=101),
     *     @OA\Property(property="user_id", type="integer", example=5),
     *     @OA\Property(property="promotion_plan_id", type="integer", example=1),
     *     @OA\Property(property="amount_paid", type="number", format="float", example=9000),
     *     @OA\Property(property="currency", type="string", example="UZS"),
     *     @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="status", type="string", enum={"pending","paid","active","expired","cancelled"}, example="pending"),
     *     @OA\Property(property="payment_transaction_id", type="string", nullable=true),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time"),
     *     @OA\Property(property="promotion_plan", ref="#/components/schemas/AdPromotionPlanSummary")
     * )
     * @OA\Schema(
     *     schema="AdPromotionStoreSuccessResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(property="data", ref="#/components/schemas/AdPromotionOrderItem")
     * )
     */
    private function _swaggerSchemas(): void {}
}
