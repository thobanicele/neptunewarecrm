<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }

            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('tenant_id');
            }

            // Only add this if you actually use it
            if (!Schema::hasColumn('users', 'is_platform_owner')) {
                $table->boolean('is_platform_owner')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // drop in reverse, only if they exist
            if (Schema::hasColumn('users', 'is_platform_owner')) {
                $table->dropColumn('is_platform_owner');
            }

            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('users', 'tenant_id')) {
                // if you created an index above, drop it too (if present)
                // MySQL auto-names it sometimes; safest is:
                // $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};

