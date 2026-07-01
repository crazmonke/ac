<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('audience_scope', 20)
                ->default('all')
                ->after('visibility');
            $table->index(['audience_scope', 'created_at']);
        });

        // Legacy resident-only posts are treated as apartment-limited detail posts.
        DB::table('posts')
            ->where('visibility', 'resident_only')
            ->update(['audience_scope' => 'apartment']);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['audience_scope', 'created_at']);
            $table->dropColumn('audience_scope');
        });
    }
};
