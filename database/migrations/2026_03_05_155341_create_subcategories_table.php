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
        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete()->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();

            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->integer('sort_order')->nullable();
            $table->boolean('is_active');
            $table->timestamps();
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('subcategories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        Schema::dropIfExists('subcategories');
    }
};
