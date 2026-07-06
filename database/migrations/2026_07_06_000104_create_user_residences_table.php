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
        Schema::create('user_residences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('complex_id')->constrained('residence_complexes')->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('residence_buildings')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('residence_units')->nullOnDelete();
            $table->string('verification_method', 20)->default('gps');
            $table->string('verification_status', 20)->default('pending');
            $table->timestamp('gps_verified_at')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->json('evidence_meta')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'verification_status']);
            $table->index(['complex_id', 'verification_status']);
            $table->unique(['user_id', 'complex_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_residences');
    }
};
