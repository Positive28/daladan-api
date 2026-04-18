<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdPromotion;
use App\Services\AdPromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * Admin: promo buyurtmalari ro'yxati + tasdiq (Click/Payme dan keyin). ads bilan sinxron AdPromotionService orqali.
 */
class AdminAdPromotionController extends Controller
{
    public function __construct(
        private readonly AdPromotionService $promotionService
    ) {
        $this->middleware(['auth:api', 'admin']);
    }

    /**
     * Admin panel: pullik promo buyurtmalari (odatda kutilayotganlar).
     *
     * @queryParam status pending|paid|active|expired|cancelled|all (default: pending)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $allowed = [
            AdPromotion::STATUS_PENDING,
            AdPromotion::STATUS_PAID,
            AdPromotion::STATUS_ACTIVE,
            AdPromotion::STATUS_EXPIRED,
            AdPromotion::STATUS_CANCELLED,
        ];

        $status = (string) $request->query('status', AdPromotion::STATUS_PENDING);

        $query = AdPromotion::query()
            ->with([
                'ad:id,title,status,seller_id',
                'promotionPlan:id,name,slug,type,price,currency,duration_days',
                'user:id,phone,fname,lname',
            ])
            ->orderByDesc('created_at');

        if ($status === 'all') {
            // filter yo'q
        } elseif (in_array($status, $allowed, true)) {
            $query->where('status', $status);
        } else {
            $query->where('status', AdPromotion::STATUS_PENDING);
        }

        return response()->successJson($query->paginate($perPage));
    }

    /**
     * Click/Payme dan keyin admin to‘lovni ko‘rib, pending buyurtmani faollashtiradi — ads bilan sinxron.
     */
    public function confirm(Request $request, AdPromotion $promotion): JsonResponse
    {
        $validated = $request->validate([
            'payment_transaction_id' => 'nullable|string|max:100',
        ]);

        try {
            $this->promotionService->confirmPending(
                $promotion,
                $validated['payment_transaction_id'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->errorJson($e->getMessage(), 422);
        }

        $promotion->refresh()->load(['ad:id,title,status', 'promotionPlan:id,name,slug,type', 'user:id,phone,fname']);

        return response()->successJson($promotion);
    }

    // =========================================================================
    // Swagger / OpenAPI annotations
    // =========================================================================

    /**
     * index() — GET /admin/ad-promotions
     * @OA\Get(
     *     path="/admin/ad-promotions",
     *     tags={"Admin Promotions"},
     *     summary="Promo buyurtmalari (admin panel ro'yxati)",
     *     description="Default: faqat pending. status=all yoki boshqa holat — filter.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="pending (default) | paid | active | expired | cancelled | all",
     *         @OA\Schema(type="string", example="pending")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginatsiyali buyurtmalar",
     *         @OA\JsonContent(ref="#/components/schemas/AdminAdPromotionListSuccessResponse")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerIndex(): void {}

    /**
     * confirm() — PATCH /admin/ad-promotions/{promotion}/confirm
     * @OA\Patch(
     *     path="/admin/ad-promotions/{promotion}/confirm",
     *     tags={"Admin Promotions"},
     *     summary="Pending promo buyurtmasini tasdiqlash (active + ads sinxron)",
     *     description="Click/Payme dan keyin ixtiyoriy payment_transaction_id. Faqat pending holat.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="promotion", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false,
     *         @OA\JsonContent(ref="#/components/schemas/AdminAdPromotionConfirmPayload")
     *     ),
     *     @OA\Response(response=200, description="Tasdiqlandi", @OA\JsonContent(ref="#/components/schemas/AdminAdPromotionConfirmSuccessResponse")),
     *     @OA\Response(response=422, description="Holat yoki validatsiya"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerConfirm(): void {}

    /**
     * @OA\Schema(
     *     schema="AdminAdPromotionConfirmPayload",
     *     type="object",
     *     @OA\Property(property="payment_transaction_id", type="string", maxLength=100, nullable=true, example="click_abc123")
     * )
     * @OA\Schema(
     *     schema="AdminAdPromotionAdSnippet",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=101),
     *     @OA\Property(property="title", type="string", example="Naslli echkilar"),
     *     @OA\Property(property="status", type="string", example="active")
     * )
     * @OA\Schema(
     *     schema="AdminAdPromotionPlanSnippet",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Top sotuv — 7 kun"),
     *     @OA\Property(property="slug", type="string", example="top-sale-7d"),
     *     @OA\Property(property="type", type="string", enum={"top_sale","boost"})
     * )
     * @OA\Schema(
     *     schema="AdminAdPromotionUserSnippet",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=5),
     *     @OA\Property(property="phone", type="string", example="+998901234567"),
     *     @OA\Property(property="fname", type="string", example="Ali")
     * )
     * @OA\Schema(
     *     schema="AdminAdPromotionDetail",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="ad_id", type="integer", example=101),
     *     @OA\Property(property="user_id", type="integer", example=5),
     *     @OA\Property(property="promotion_plan_id", type="integer", example=1),
     *     @OA\Property(property="amount_paid", type="number", format="float", example=9000),
     *     @OA\Property(property="currency", type="string", example="UZS"),
     *     @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="status", type="string", example="active"),
     *     @OA\Property(property="payment_transaction_id", type="string", nullable=true),
     *     @OA\Property(property="ad", ref="#/components/schemas/AdminAdPromotionAdSnippet"),
     *     @OA\Property(property="promotion_plan", ref="#/components/schemas/AdminAdPromotionPlanSnippet"),
     *     @OA\Property(property="user", ref="#/components/schemas/AdminAdPromotionUserSnippet")
     * )
     * @OA\Schema(
     *     schema="AdminAdPromotionConfirmSuccessResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(property="data", ref="#/components/schemas/AdminAdPromotionDetail")
     * )
     * @OA\Schema(
     *     schema="AdminAdPromotionListSuccessResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="ok"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=3),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AdminAdPromotionDetail"))
     *     )
     * )
     * @OA\Tag(
     *     name="Admin Promotions",
     *     description="Pullik top/boost buyurtmalari: ro'yxat va tasdiq"
     * )
     */
    private function _swaggerSchemas(): void {}
}
