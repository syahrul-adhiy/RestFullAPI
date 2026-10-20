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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->string('sku', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->string('material', 80)->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->string('image_path')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index('price');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
