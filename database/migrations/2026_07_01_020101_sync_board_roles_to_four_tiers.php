<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $legacyVerifiedRoles = [
        'resident',
        'household_rep',
        'owner_verified',
        'tenant_verified',
    ];

    public function up(): void
    {
        foreach (['read_role', 'write_role', 'comment_role'] as $column) {
            DB::table('boards')
                ->whereIn($column, $this->legacyVerifiedRoles)
                ->update([$column => 'verified']);
        }
    }

    public function down(): void
    {
        foreach (['read_role', 'write_role', 'comment_role'] as $column) {
            DB::table('boards')
                ->where($column, 'verified')
                ->update([$column => 'resident']);
        }
    }
};
