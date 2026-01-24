<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Add tenant_id if missing
            if (!Schema::hasColumn('companies', 'tenant_id')) {
                $table->foreignId('tenant_id')->after('id')
                    ->constrained('tenants')->cascadeOnDelete();
            }

            if (!Schema::hasColumn('companies', 'name')) {
                $table->string('name')->after('tenant_id');
            }

            if (!Schema::hasColumn('companies', 'type')) {
                $table->string('type')->default('prospect')->after('name'); // prospect|customer|individual
            }

            if (!Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable()->after('type');
            }

            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (!Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('companies', 'industry')) {
                $table->string('industry')->nullable()->after('website');
            }

            if (!Schema::hasColumn('companies', 'address')) {
                $table->text('address')->nullable()->after('industry');
            }

            // Useful index
            $table->index(['tenant_id', 'type'], 'companies_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Drop index if it exists
            try { $table->dropIndex('companies_tenant_type_idx'); } catch (\Throwable $e) {}

            // Drop columns in reverse order (only if they exist)
            if (Schema::hasColumn('companies', 'address')) $table->dropColumn('address');
            if (Schema::hasColumn('companies', 'industry')) $table->dropColumn('industry');
            if (Schema::hasColumn('companies', 'website')) $table->dropColumn('website');
            if (Schema::hasColumn('companies', 'phone')) $table->dropColumn('phone');
            if (Schema::hasColumn('companies', 'email')) $table->dropColumn('email');
            if (Schema::hasColumn('companies', 'type')) $table->dropColumn('type');
            if (Schema::hasColumn('companies', 'name')) $table->dropColumn('name');

            if (Schema::hasColumn('companies', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }
        });
    }
};

