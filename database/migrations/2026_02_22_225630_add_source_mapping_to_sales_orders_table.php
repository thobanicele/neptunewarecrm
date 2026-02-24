<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            // e.g. 'crm', 'ecommerce'
            $table->string('source', 30)->default('crm')->after('tenant_id');

            // Links sales order to ecommerce order id when created via conversion
            $table->unsignedBigInteger('source_id')->nullable()->after('source');

            // Optional: copy external_order_id for easy search
            $table->string('external_order_id', 100)->nullable()->after('source_id');

            $table->index(['tenant_id', 'source']);
            $table->index(['tenant_id', 'external_order_id']);

            // Enforce one SalesOrder per EcommerceOrder (idempotent conversion)
            $table->unique(['tenant_id', 'source', 'source_id'], 'sales_orders_tenant_source_sourceid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropUnique('sales_orders_tenant_source_sourceid_unique');
            $table->dropIndex(['tenant_id', 'source']);
            $table->dropIndex(['tenant_id', 'external_order_id']);

            $table->dropColumn(['source', 'source_id', 'external_order_id']);
        });
    }
};
