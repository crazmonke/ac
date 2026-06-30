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
        Schema::create('board_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->string('slug', 80);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->unique(['apartment_id', 'slug']);
            $table->index(['apartment_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_categories');
    }
};
