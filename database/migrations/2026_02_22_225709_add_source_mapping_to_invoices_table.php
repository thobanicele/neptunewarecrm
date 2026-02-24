<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // e.g. 'sales_order'
            $table->string('source', 30)->nullable()->after('tenant_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source');

            $table->index(['tenant_id', 'source']);
            $table->unique(['tenant_id', 'source', 'source_id'], 'invoices_tenant_source_sourceid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_tenant_source_sourceid_unique');
            $table->dropIndex(['tenant_id', 'source']);
            $table->dropColumn(['source', 'source_id']);
        });
    }
};
