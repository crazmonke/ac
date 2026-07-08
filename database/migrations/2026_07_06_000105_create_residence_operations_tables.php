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
        if (! Schema::hasTable('residence_name_suggestions')) {
            Schema::create('residence_name_suggestions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('complex_id')->constrained('residence_complexes')->cascadeOnDelete();
                $table->string('suggested_name', 150);
                $table->foreignId('suggested_by')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('votes_up')->default(0);
                $table->unsignedInteger('votes_down')->default(0);
                $table->string('status', 20)->default('pending');
                $table->timestamps();

                $table->index(['complex_id', 'status']);
            });
        }

        if (! Schema::hasTable('residence_merge_candidates')) {
            Schema::create('residence_merge_candidates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_complex_id')->constrained('residence_complexes')->cascadeOnDelete();
                $table->foreignId('target_complex_id')->constrained('residence_complexes')->cascadeOnDelete();
                $table->decimal('score', 5, 2)->default(0);
                $table->json('reason')->nullable();
                $table->string('status', 20)->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['source_complex_id', 'target_complex_id'], 'rmc_source_target_unique');
                $table->index(['status', 'score']);
            });
        }

        if (! Schema::hasTable('operational_metrics')) {
            Schema::create('operational_metrics', function (Blueprint $table) {
                $table->id();
                $table->string('event_name', 80);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('complex_id')->nullable()->constrained('residence_complexes')->nullOnDelete();
                $table->foreignId('building_id')->nullable()->constrained('residence_buildings')->nullOnDelete();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['event_name', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_metrics');
        Schema::dropIfExists('residence_merge_candidates');
        Schema::dropIfExists('residence_name_suggestions');
    }
};
