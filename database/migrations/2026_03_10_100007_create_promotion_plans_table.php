<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sotiladigan promo paketlar katalogi (masalan: Top 7 kun, Boost 1 oy).
 * Ro'yxat: GET /api/v1/resources/promotion-plans. To'ldirish: PromotionPlanSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique(); // updateOrCreate kaliti (seed)
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency', 10)->default('UZS');
            // AdPromotionService: boshlash ertadan 00:00, tugash = boshlash + duration_days.
            $table->unsignedSmallInteger('duration_days');
            // PromotionPlan::TYPE_TOP_SALE yoki TYPE_BOOST — ads.is_top_sale / is_boosted bilan mos.
            $table->string('type', 30);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_plans');
    }
};
