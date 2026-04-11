<?php

namespace App\Services;

use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdViewService
{
    public function record(Ad $ad, Request $request): void
    {
        $userId = auth('api')->id();

        if ($userId !== null && (int) $userId === (int) $ad->seller_id) {
            return;
        }

        $cacheKey = $userId !== null
            ? "ad_view:{$ad->id}:user_{$userId}"
            : 'ad_view:'.$ad->id.':ip_'.($request->ip() ?? '');

        if (Cache::has($cacheKey)) {
            return;
        }

        DB::transaction(function () use ($ad, $userId, $request): void {
            DB::table('ad_views')->insert([
                'ad_id'       => $ad->id,
                'user_id'     => $userId,
                'ip_address'  => $userId !== null ? null : $request->ip(),
                'user_agent'  => substr($request->userAgent() ?? '', 0, 255),
                'viewed_at'   => now(),
            ]);

            DB::table('ads')->where('id', $ad->id)->increment('views_count');
        });

        Cache::put($cacheKey, true, now()->addHour());
    }
}
