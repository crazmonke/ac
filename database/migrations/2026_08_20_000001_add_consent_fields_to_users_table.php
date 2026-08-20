<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_adult')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_adult')->default(false)->after('email');
                $table->timestamp('terms_agreed_at')->nullable()->after('is_adult');
                $table->timestamp('privacy_agreed_at')->nullable()->after('terms_agreed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_adult')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['is_adult', 'terms_agreed_at', 'privacy_agreed_at']);
            });
        }
    }
};