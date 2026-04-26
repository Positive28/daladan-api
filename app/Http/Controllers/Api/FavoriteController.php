<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $favorites = $request->user()
            ->favorites()
            ->where('ads.status', Ad::STATUS_ACTIVE)
            ->with(['category:id,name', 'subcategory:id,name', 'seller'])
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage);

        return response()->successJson($favorites);
    }

    public function store(Request $request, Ad $ad): JsonResponse
    {
        if ($ad->status !== Ad::STATUS_ACTIVE) {
            return response()->errorJson('Faqat faol e\'lonni sevimlilarga qo\'shish mumkin.', 422);
        }

        $request->user()->favorites()->syncWithoutDetaching([$ad->id]);

        return response()->successJson([
            'message' => 'Sevimlilarga qo\'shildi.',
            'ad_id' => $ad->id,
            'is_favorited' => true,
        ]);
    }

    public function destroy(Request $request, Ad $ad): JsonResponse
    {
        $request->user()->favorites()->detach($ad->id);

        return response()->successJson([
            'message' => 'Sevimlilardan olib tashlandi.',
            'ad_id' => $ad->id,
            'is_favorited' => false,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/profile/favorites",
     *     tags={"Profile Favorites"},
     *     summary="Sevimli e'lonlar ro'yxati",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=15)),
     *     @OA\Response(response=200, description="Sevimli e'lonlar"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerIndex(): void {}

    /**
     * @OA\Post(
     *     path="/profile/favorites/{ad}",
     *     tags={"Profile Favorites"},
     *     summary="E'lonni sevimlilarga qo'shish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ad", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Qo'shildi"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Faol bo'lmagan e'lon")
     * )
     */
    private function _swaggerStore(): void {}

    /**
     * @OA\Delete(
     *     path="/profile/favorites/{ad}",
     *     tags={"Profile Favorites"},
     *     summary="E'lonni sevimlilardan olib tashlash",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="ad", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Olib tashlandi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    private function _swaggerDestroy(): void {}
}
