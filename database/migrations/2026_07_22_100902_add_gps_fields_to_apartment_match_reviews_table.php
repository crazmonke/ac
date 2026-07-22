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
        Schema::table('apartment_match_reviews', function (Blueprint $table) {
            $table->string('road_address', 255)->nullable()->after('raw_region');
            $table->double('latitude')->nullable()->after('road_address');
            $table->double('longitude')->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartment_match_reviews', function (Blueprint $table) {
            $table->dropColumn(['road_address', 'latitude', 'longitude']);
        });
    }
};
