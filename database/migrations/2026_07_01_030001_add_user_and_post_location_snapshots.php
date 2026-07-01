<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('home_sido', 50)->nullable()->after('preferred_apartment_id');
            $table->string('home_sigungu', 80)->nullable()->after('home_sido');
            $table->string('home_eupmyeondong', 80)->nullable()->after('home_sigungu');
            $table->string('home_apartment_name', 160)->nullable()->after('home_eupmyeondong');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('region_sido', 50)->nullable()->after('apartment_id');
            $table->string('region_sigungu', 80)->nullable()->after('region_sido');
            $table->string('region_eupmyeondong', 80)->nullable()->after('region_sigungu');
            $table->index(['region_sido', 'region_sigungu', 'created_at']);
        });

        DB::statement('UPDATE users SET home_sido = (SELECT sido FROM apartments WHERE apartments.id = users.preferred_apartment_id), home_sigungu = (SELECT sigungu FROM apartments WHERE apartments.id = users.preferred_apartment_id), home_eupmyeondong = (SELECT eupmyeondong FROM apartments WHERE apartments.id = users.preferred_apartment_id), home_apartment_name = (SELECT name FROM apartments WHERE apartments.id = users.preferred_apartment_id) WHERE preferred_apartment_id IS NOT NULL');

        DB::statement('UPDATE posts SET region_sido = (SELECT sido FROM apartments WHERE apartments.id = posts.apartment_id), region_sigungu = (SELECT sigungu FROM apartments WHERE apartments.id = posts.apartment_id), region_eupmyeondong = (SELECT eupmyeondong FROM apartments WHERE apartments.id = posts.apartment_id) WHERE apartment_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['region_sido', 'region_sigungu', 'created_at']);
            $table->dropColumn(['region_sido', 'region_sigungu', 'region_eupmyeondong']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['home_sido', 'home_sigungu', 'home_eupmyeondong', 'home_apartment_name']);
        });
    }
};
