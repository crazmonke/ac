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
        Schema::create('post_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('템플릿 이름');
            $table->string('description')->nullable()->comment('템플릿 설명 (선택 모달에 노출)');
            $table->string('title_template')->comment('제목 생성 규칙 ({q1}~{q10} placeholder)');
            $table->json('questions')->comment('질문 목록 JSON (최대 10개)');
            $table->json('board_slugs')->nullable()->comment('사용 가능 게시판 slug 목록 (null=전체)');
            $table->integer('sort_order')->default(0)->comment('정렬순서');
            $table->boolean('is_active')->default(true)->comment('사용 여부');
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_templates');
    }
};
