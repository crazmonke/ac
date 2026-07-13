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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('배너 제목');
            $table->text('description')->nullable()->comment('배너 설명');
            $table->enum('type', ['image', 'video', 'text'])->default('image')->comment('배너 유형');
            $table->string('image_url')->nullable()->comment('이미지 URL');
            $table->string('video_url')->nullable()->comment('영상 URL');
            $table->text('text_content')->nullable()->comment('텍스트 내용');
            $table->string('button_text')->nullable()->comment('버튼 텍스트');
            $table->string('button_url')->nullable()->comment('버튼 링크');
            $table->integer('sort_order')->default(0)->comment('정렬순서');
            $table->boolean('is_active')->default(true)->comment('노출 여부');
            $table->date('start_date')->nullable()->comment('노출 시작일');
            $table->date('end_date')->nullable()->comment('노출 종료일');
            $table->timestamps();
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
