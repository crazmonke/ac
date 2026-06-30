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
        Schema::create('apartment_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained('apartments')->cascadeOnDelete();
            $table->string('alias', 120);
            $table->string('normalized_alias', 120);
            $table->string('source', 40)->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['apartment_id', 'normalized_alias']);
            $table->index(['normalized_alias', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment_aliases');
    }
};
