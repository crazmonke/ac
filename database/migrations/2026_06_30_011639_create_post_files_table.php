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
        Schema::create('post_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('disk', 20)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_files');
    }
};
