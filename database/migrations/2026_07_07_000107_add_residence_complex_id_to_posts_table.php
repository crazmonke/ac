<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('residence_complex_id')
                ->nullable()
                ->after('apartment_id')
                ->constrained('residence_complexes')
                ->nullOnDelete();

            $table->index(['residence_complex_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['residence_complex_id', 'created_at']);
            $table->dropConstrainedForeignId('residence_complex_id');
        });
    }
};
