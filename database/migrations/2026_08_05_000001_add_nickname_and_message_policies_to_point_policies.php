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
        Schema::table('point_policies', function (Blueprint $table) {
            $table->unsignedInteger('nickname_change_points')->default(100)->after('min_spend_points')->comment('닉네임 변경 차감 포인트 (0=무료)');
            $table->unsignedInteger('daily_free_messages')->default(5)->after('nickname_change_points')->comment('쪽지 일일 무료 발송 건수');
            $table->unsignedInteger('message_send_points')->default(10)->after('daily_free_messages')->comment('무료 소진 후 쪽지 발송 건당 차감 포인트 (0=무제한 무료)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_policies', function (Blueprint $table) {
            $table->dropColumn(['nickname_change_points', 'daily_free_messages', 'message_send_points']);
        });
    }
};
