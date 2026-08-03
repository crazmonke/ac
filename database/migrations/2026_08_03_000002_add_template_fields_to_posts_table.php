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
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('post_template_id')->nullable()->after('post_topic_id')
                ->constrained('post_templates')->nullOnDelete()->comment('작성에 사용된 템플릿');
            $table->json('template_answers')->nullable()->after('body')->comment('템플릿 답변 JSON {q1: ...}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_template_id');
            $table->dropColumn('template_answers');
        });
    }
};
