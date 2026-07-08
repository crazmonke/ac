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
        Schema::create('residence_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('residence_buildings')->cascadeOnDelete();
            $table->string('dong', 40)->nullable();
            $table->string('ho', 40)->nullable();
            $table->string('unit_label_generated', 120);
            $table->string('normalized_unit_key', 80);
            $table->timestamps();

            $table->unique(['building_id', 'normalized_unit_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residence_units');
    }
};
