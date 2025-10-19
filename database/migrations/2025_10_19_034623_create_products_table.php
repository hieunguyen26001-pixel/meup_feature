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
            $table->string('tiktok_product_id')->unique(); // TikTok product ID
            $table->string('shop_id'); // Shop ID từ TikTok
            $table->string('title'); // Tên sản phẩm
            $table->text('description')->nullable(); // Mô tả
            $table->string('status'); // Trạng thái sản phẩm
            $table->json('images')->nullable(); // Danh sách hình ảnh
            $table->json('skus')->nullable(); // Danh sách SKUs với giá và stock
            $table->json('categories')->nullable(); // Danh mục sản phẩm
            $table->json('attributes')->nullable(); // Thuộc tính sản phẩm
            $table->decimal('min_price', 10, 2)->nullable(); // Giá thấp nhất
            $table->decimal('max_price', 10, 2)->nullable(); // Giá cao nhất
            $table->integer('total_stock')->default(0); // Tổng số lượng kho
            $table->timestamp('tiktok_created_at')->nullable(); // Thời gian tạo trên TikTok
            $table->timestamp('tiktok_updated_at')->nullable(); // Thời gian cập nhật trên TikTok
            $table->timestamp('last_synced_at')->nullable(); // Thời gian sync cuối
            $table->timestamps();
            
            // Indexes
            $table->index('shop_id');
            $table->index('status');
            $table->index('last_synced_at');
            $table->index(['shop_id', 'status']);
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