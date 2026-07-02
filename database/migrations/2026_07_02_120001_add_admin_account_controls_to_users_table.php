<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->boolean('access_allowed')->default(true)->after('last_login_at');
            $table->timestamp('withdrawn_at')->nullable()->after('access_allowed');
            $table->boolean('profile_locked')->default(true)->after('withdrawn_at');
            $table->index(['access_allowed', 'withdrawn_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['access_allowed', 'withdrawn_at']);
            $table->dropColumn(['last_login_at', 'access_allowed', 'withdrawn_at', 'profile_locked']);
        });
    }
};
