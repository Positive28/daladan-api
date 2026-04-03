<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id')->unique();
            $table->foreign('ad_id')->references('id')->on('ads')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            // Tur va navi
            $table->string('grain_type', 100)->nullable();               // bug'doy, sholi, makkajo'xori, arpa...
            $table->string('variety', 100)->nullable();       // navi: Krasnodarskiy, Basmati...
            // Miqdor va o'lchov
            $table->enum('unit', ['kg', 'ton', 'bag'])->default('ton'); // xalta, kg, tonna
            $table->unsignedBigInteger('price');               // 1 kg/dona narxi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grains');
    }
};
