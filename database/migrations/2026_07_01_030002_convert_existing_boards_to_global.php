<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('boards')->update(['apartment_id' => null]);
        DB::table('board_categories')->update(['apartment_id' => null]);
    }

    public function down(): void
    {
        // Irreversible without historical apartment mapping.
    }
};
