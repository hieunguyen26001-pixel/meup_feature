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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id', 255)->unique()->index(); // TikTok Shop ID
            $table->string('shop_name', 255);
            $table->string('region', 10)->default('VN'); // VN, US, etc.
            $table->string('seller_type', 50)->nullable(); // SELLER, AFFILIATE, etc.
            $table->string('seller_cipher', 255)->nullable(); // Shop cipher for API calls
            $table->json('scopes')->nullable(); // Granted scopes
            $table->json('metadata')->nullable(); // Additional shop info
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            
            $table->index(['shop_id', 'is_active']);
            $table->index('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
