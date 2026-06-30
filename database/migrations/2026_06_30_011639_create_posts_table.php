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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('body');
            $table->boolean('is_notice')->default(false);
            $table->boolean('is_anonymous')->default(false);
            $table->string('visibility', 20)->default('resident_only');
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['board_id', 'created_at']);
            $table->index(['apartment_id', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
