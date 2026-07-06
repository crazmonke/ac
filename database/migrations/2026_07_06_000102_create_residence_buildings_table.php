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
        Schema::create('residence_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->constrained('residence_complexes')->cascadeOnDelete();
            $table->string('building_no', 40)->nullable();
            $table->string('building_name', 120)->nullable();
            $table->string('road_address', 255)->nullable();
            $table->string('jibun_address', 255)->nullable();
            $table->string('bld_main_no', 20)->nullable();
            $table->string('bld_sub_no', 20)->nullable();
            $table->string('legal_dong_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('normalized_key', 190);
            $table->timestamps();

            $table->unique('normalized_key');
            $table->index(['complex_id', 'building_no']);
            $table->index('building_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residence_buildings');
    }
};
