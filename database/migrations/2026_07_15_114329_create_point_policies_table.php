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
        Schema::create('point_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('post_points')->default(1);
            $table->unsignedInteger('comment_points')->default(1);
            $table->unsignedInteger('daily_max_points')->default(10);
            $table->unsignedInteger('min_spend_points')->default(1000);
            $table->unsignedSmallInteger('expiry_months')->nullable()->comment('null=소멸없음');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('point_policies')->insert([
            'post_points'      => 1,
            'comment_points'   => 1,
            'daily_max_points' => 10,
            'min_spend_points' => 1000,
            'expiry_months'    => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_policies');
    }
};
