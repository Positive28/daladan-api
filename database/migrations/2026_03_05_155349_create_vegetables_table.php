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
        Schema::create('vegetables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id')->unique();
            $table->foreign('ad_id')->references('id')->on('ads')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            // Tur va navi
            $table->string('vegetable_type', 100);            // pomidor, kartoshka, piyoz...
            $table->string('variety', 100)->nullable();        // navi: Cherry, Belorussiya...
            // Miqdor
            $table->decimal('quantity', 10, 2);
            $table->enum('unit', ['kg', 'ton', 'box', 'piece'])->default('kg');
            // Narx
            $table->unsignedBigInteger('price');               // 1 kg/dona narxi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vegetables');
    }
};
