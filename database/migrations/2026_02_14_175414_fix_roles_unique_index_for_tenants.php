<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop indexes safely via raw SQL if they exist
        $indexes = collect(DB::select("SHOW INDEX FROM roles"))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        Schema::table('roles', function (Blueprint $table) use ($indexes) {
            // Drop old global unique if present
            if (in_array('roles_name_guard_name_unique', $indexes, true)) {
                $table->dropUnique('roles_name_guard_name_unique');
            }

            // Drop tenant unique if present (we will recreate to ensure correct columns)
            if (in_array('roles_tenant_name_guard_unique', $indexes, true)) {
                $table->dropUnique('roles_tenant_name_guard_unique');
            }
        });

        // Re-add correct tenant-scoped unique
        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_tenant_name_guard_unique');
        });
    }

    public function down(): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM roles"))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        Schema::table('roles', function (Blueprint $table) use ($indexes) {
            if (in_array('roles_tenant_name_guard_unique', $indexes, true)) {
                $table->dropUnique('roles_tenant_name_guard_unique');
            }
        });

        Schema::table('roles', function (Blueprint $table) use ($indexes) {
            // restore original global unique
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
        });
    }
};

