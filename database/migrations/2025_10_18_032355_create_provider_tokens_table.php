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
        Schema::create('provider_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->default('SHOP')->index();
            $table->string('subject_id', 255)->index(); // shop_id
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->text('scope')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('refresh_expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['provider', 'subject_id']);
            $table->index(['provider', 'expires_at']);
            $table->index(['provider', 'refresh_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_tokens');
    }
};
