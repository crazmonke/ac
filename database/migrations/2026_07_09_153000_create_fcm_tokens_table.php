<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform', 32)->nullable();
            $table->string('device_id')->nullable()->index();
            $table->string('device_name')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();

            $table->index(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};