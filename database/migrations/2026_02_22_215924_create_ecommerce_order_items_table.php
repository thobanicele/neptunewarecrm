<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ecommerce_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('ecommerce_order_id')->constrained('ecommerce_orders')->cascadeOnDelete();

            // Optional idempotency for line items (external line id). If not provided, we upsert by (order_id, sku, position).
            $table->string('external_item_id', 100)->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->string('sku', 80)->nullable();
            $table->string('name')->nullable();

            $table->decimal('qty', 12, 2)->default(1);

            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'ecommerce_order_id']);
            $table->index(['tenant_id', 'sku']);

            // Allows safe upserts when external_item_id is present
            $table->unique(
                ['tenant_id', 'ecommerce_order_id', 'external_item_id'],
                'ecomm_items_tenant_order_external_item_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_items');
    }
};
