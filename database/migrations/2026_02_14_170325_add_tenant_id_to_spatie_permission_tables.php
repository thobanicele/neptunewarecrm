<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // roles
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            }
        });

        // permissions (optional but recommended if you want per-tenant permissions too)
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            }
        });

        // model_has_roles
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_roles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('role_id');
            }
        });

        // model_has_permissions
        Schema::table('model_has_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_permissions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('permission_id');
            }
        });

        // role_has_permissions (optional but recommended in team mode)
        Schema::table('role_has_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('role_has_permissions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('permission_id');
            }
        });
    }

    public function down(): void
    {
        foreach (['roles','permissions','model_has_roles','model_has_permissions','role_has_permissions'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'tenant_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};

