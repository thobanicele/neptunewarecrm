<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $tableNames  = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams       = (bool) config('permission.teams');
        $rolesTable  = $tableNames['roles'];

        if (!$teams) return; // only applies to teams mode

        $teamKey = $columnNames['team_foreign_key'] ?? 'tenant_id';

        // ✅ Drop common duplicate/legacy index names if they exist
        $possible = [
            'roles_tenant_name_guard_unique',
            'roles_team_foreign_key_name_guard_name_unique',
            'roles_'.$teamKey.'_name_guard_name_unique',
        ];

        foreach ($possible as $idx) {
            $exists = DB::selectOne("
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                  AND index_name = ?
                LIMIT 1
            ", [$rolesTable, $idx]);

            if ($exists) {
                DB::statement("ALTER TABLE `{$rolesTable}` DROP INDEX `{$idx}`");
            }
        }

        // ✅ Recreate correct unique index (teams mode)
        Schema::table($rolesTable, function (Blueprint $table) use ($teamKey) {
            $table->unique([$teamKey, 'name', 'guard_name'], 'roles_'.$teamKey.'_name_guard_name_unique');
        });
    }

    public function down(): void
    {
        $tableNames  = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolesTable  = $tableNames['roles'];
        $teamKey     = $columnNames['team_foreign_key'] ?? 'tenant_id';

        // Drop the index we created
        Schema::table($rolesTable, function (Blueprint $table) use ($teamKey) {
            $table->dropUnique('roles_'.$teamKey.'_name_guard_name_unique');
        });
    }
};

