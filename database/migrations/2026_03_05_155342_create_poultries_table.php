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
        Schema::create('poultries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id')->unique();
            $table->foreign('ad_id')->references('id')->on('ads')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            // Tur va zot
            $table->string('poultry_type', 100);              // tovuq, o'rdak, g'oz, kurka, bedana...
            $table->string('breed', 100)->nullable();          // zoti: Broiler, Leghorn, Pekin...
            $table->unsignedBigInteger('price_per_head');     // 1 donasi narxi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poultries');
    }
};
