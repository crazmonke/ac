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
        Schema::table('apartments', function (Blueprint $table) {
            $table->string('source', 40)->nullable()->after('eupmyeondong');
            $table->string('source_key', 120)->nullable()->after('source');
            $table->string('normalized_name', 120)->nullable()->after('source_key');
            $table->boolean('is_active')->default(true)->after('normalized_name');
            $table->timestamp('synced_at')->nullable()->after('is_active');

            $table->unique(['source', 'source_key']);
            $table->index(['sido', 'sigungu', 'normalized_name']);
            $table->index(['source', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropUnique('apartments_source_source_key_unique');
            $table->dropIndex('apartments_sido_sigungu_normalized_name_index');
            $table->dropIndex('apartments_source_is_active_index');

            $table->dropColumn([
                'source',
                'source_key',
                'normalized_name',
                'is_active',
                'synced_at',
            ]);
        });
    }
};
