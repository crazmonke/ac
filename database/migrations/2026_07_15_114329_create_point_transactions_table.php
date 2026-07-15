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
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earn', 'deduct', 'expire']);
            $table->enum('source', ['post', 'comment', 'admin', 'system']);
            $table->unsignedBigInteger('source_id')->nullable()->comment('post_id or comment_id');
            $table->unsignedBigInteger('source_post_id')->nullable()->comment('댓글 적립 시 해당 post_id');
            $table->integer('amount')->comment('양수=적립, 음수=차감/소멸');
            $table->integer('balance_after')->default(0);
            $table->string('note', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['source', 'source_id']);
            $table->index(['user_id', 'source', 'source_post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
