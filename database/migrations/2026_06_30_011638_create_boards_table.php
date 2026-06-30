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
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('board_categories')->cascadeOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 80);
            $table->text('description')->nullable();
            $table->string('board_type', 30)->default('normal');
            $table->string('read_role', 30)->default('resident');
            $table->string('write_role', 30)->default('resident');
            $table->string('comment_role', 30)->default('resident');
            $table->boolean('allow_file')->default(true);
            $table->boolean('allow_anonymous')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['apartment_id', 'slug']);
            $table->index(['category_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
