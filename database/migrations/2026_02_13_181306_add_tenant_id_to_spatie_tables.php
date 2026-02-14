<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // roles table
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unique(['tenant_id','name','guard_name'], 'roles_tenant_name_guard_unique');
            }
        });

        // model_has_roles
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_roles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();

                // Spatie teams expects this composite PK shape (or close to it)
                // If your table already has a PK, don't drop it blindly.
                $table->index(['tenant_id','model_id','model_type'], 'mhr_tenant_model_idx');
            }
        });

        // model_has_permissions
        Schema::table('model_has_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_permissions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->index(['tenant_id','model_id','model_type'], 'mhp_tenant_model_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'tenant_id')) {
                $table->dropUnique('roles_tenant_name_guard_unique');
                $table->dropColumn('tenant_id');
            }
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            if (Schema::hasColumn('model_has_roles', 'tenant_id')) {
                $table->dropIndex('mhr_tenant_model_idx');
                $table->dropColumn('tenant_id');
            }
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('model_has_permissions', 'tenant_id')) {
                $table->dropIndex('mhp_tenant_model_idx');
                $table->dropColumn('tenant_id');
            }
        });
    }
};


