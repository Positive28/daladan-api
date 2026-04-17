<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Har bir promo buyurtmasi (tarix + to'lov holati).
 * Oqim: pending (sotuvchi yaratadi) → admin confirm → active + started_at/expires_at;
 * muddati o'tsa → expired; boshqa tasdiqda bekor → cancelled. Mantiq: AdPromotionService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // odatda sotuvchi
            $table->foreignId('promotion_plan_id')->constrained('promotion_plans')->restrictOnDelete();
            // Kutilayotgan yoki tasdiqlangan summa (planga mos); pendingda ham plan narxi yoziladi.
            $table->decimal('amount_paid', 12, 2);
            $table->string('currency', 10)->default('UZS');
            // Admin tasdiqgacha null; confirm dan keyin to'ldiriladi (promo oynasi).
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            // AdPromotion::STATUS_* — hozir asosan pending / active / expired / cancelled ishlatiladi.
            $table->string('status', 30)->default('pending');
            $table->string('payment_transaction_id', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_promotions');
    }
};
