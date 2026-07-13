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
        Schema::table('banners', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('image_url')->comment('업로드된 이미지 파일 경로');
            $table->string('video_path')->nullable()->after('video_url')->comment('업로드된 영상 파일 경로');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'video_path']);
        });
    }
};
