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
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('road_address', 255);
            $table->string('jibun_address', 255)->nullable();
            $table->string('sido', 40);
            $table->string('sigungu', 40);
            $table->string('eupmyeondong', 80);
            $table->timestamps();

            $table->index(['sido', 'sigungu', 'eupmyeondong']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
