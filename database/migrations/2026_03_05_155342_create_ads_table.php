<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('subcategory_id')->constrained('subcategories')->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();

            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('district', 100)->nullable();
            $table->unsignedBigInteger('price')->nullable();

            // Umumiy detail
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 20)->nullable(); // kg, ton, bag, box, piece

            $table->string('status', 20)->default('active'); // active, sold, deleted
            $table->boolean('is_top_sale')->default(false);
            $table->boolean('is_boosted')->default(false);
            $table->timestamp('boost_expires_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
