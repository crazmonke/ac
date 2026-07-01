<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 60);
            $table->string('slug', 80);
            $table->timestamps();

            $table->unique(['apartment_id', 'slug']);
            $table->index(['apartment_id', 'name']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('post_topic_id')->nullable()->after('board_id')->constrained('post_topics')->nullOnDelete();
            $table->index(['post_topic_id', 'created_at']);
        });

        DB::table('boards')
            ->where('slug', 'free')
            ->update([
                'read_role' => 'member',
                'write_role' => 'member',
                'comment_role' => 'member',
            ]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_topic_id');
        });

        Schema::dropIfExists('post_topics');
    }
};
