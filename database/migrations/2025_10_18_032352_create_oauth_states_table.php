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
        Schema::create('oauth_states', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->default('SHOP')->index();
            $table->string('state', 255)->unique();
            $table->string('redirect', 500)->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['provider', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_states');
    }
};
