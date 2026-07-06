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
        Schema::create('residence_complexes', function (Blueprint $table) {
            $table->id();
            $table->string('housing_type', 30)->default('apartment');
            $table->string('official_name', 150)->nullable();
            $table->string('alias_name', 150)->nullable();
            $table->string('auto_display_name', 190);
            $table->string('display_name_source', 20)->default('generated');
            $table->string('road_address', 255)->nullable();
            $table->string('jibun_address', 255)->nullable();
            $table->string('legal_dong_code', 20)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('normalized_key', 190);
            $table->string('status', 20)->default('active');
            $table->foreignId('merged_into_id')->nullable()->constrained('residence_complexes')->nullOnDelete();
            $table->foreignId('legacy_apartment_id')->nullable()->constrained('apartments')->nullOnDelete();
            $table->timestamps();

            $table->unique('normalized_key');
            $table->index(['housing_type', 'status']);
            $table->index(['legal_dong_code', 'status']);
            $table->index('official_name');
            $table->index('alias_name');
            $table->index('auto_display_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residence_complexes');
    }
};
