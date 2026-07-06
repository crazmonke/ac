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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('preferred_residence_complex_id')
                ->nullable()
                ->after('preferred_apartment_id')
                ->constrained('residence_complexes')
                ->nullOnDelete();
            $table->foreignId('preferred_residence_building_id')
                ->nullable()
                ->after('preferred_residence_complex_id')
                ->constrained('residence_buildings')
                ->nullOnDelete();
            $table->foreignId('preferred_residence_unit_id')
                ->nullable()
                ->after('preferred_residence_building_id')
                ->constrained('residence_units')
                ->nullOnDelete();
        });

        Schema::table('resident_verification_requests', function (Blueprint $table) {
            $table->foreignId('residence_complex_id')
                ->nullable()
                ->after('apartment_id')
                ->constrained('residence_complexes')
                ->nullOnDelete();
            $table->foreignId('residence_building_id')
                ->nullable()
                ->after('residence_complex_id')
                ->constrained('residence_buildings')
                ->nullOnDelete();
            $table->foreignId('residence_unit_id')
                ->nullable()
                ->after('residence_building_id')
                ->constrained('residence_units')
                ->nullOnDelete();
            $table->string('verification_method', 20)
                ->default('manual')
                ->after('status');
            $table->unsignedInteger('distance_m')->nullable()->after('verification_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_verification_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('residence_complex_id');
            $table->dropConstrainedForeignId('residence_building_id');
            $table->dropConstrainedForeignId('residence_unit_id');
            $table->dropColumn(['verification_method', 'distance_m']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_residence_complex_id');
            $table->dropConstrainedForeignId('preferred_residence_building_id');
            $table->dropConstrainedForeignId('preferred_residence_unit_id');
        });
    }
};
