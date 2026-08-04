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
        Schema::table('messages', function (Blueprint $table) {
            $table->dateTime('sender_hidden_at')->nullable()->after('read_at')->comment('발신자가 대화 삭제(감춤)한 시각');
            $table->dateTime('receiver_hidden_at')->nullable()->after('sender_hidden_at')->comment('수신자가 대화 삭제(감춤)한 시각');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sender_hidden_at', 'receiver_hidden_at']);
        });
    }
};
