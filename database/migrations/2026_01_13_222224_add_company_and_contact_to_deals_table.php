<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('deals', 'primary_contact_id')) {
                $table->foreignId('primary_contact_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('contacts')
                    ->nullOnDelete();
            }

            $table->index(['tenant_id', 'company_id', 'primary_contact_id'], 'deals_tenant_company_contact_idx');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            try { $table->dropIndex('deals_tenant_company_contact_idx'); } catch (\Throwable $e) {}

            if (Schema::hasColumn('deals', 'primary_contact_id')) {
                $table->dropConstrainedForeignId('primary_contact_id');
            }

            if (Schema::hasColumn('deals', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};

