<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ecommerce_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // Idempotency key from the ecommerce app (Shopify/Woo/Custom etc.)
            $table->string('external_order_id', 100);

            // Optional: "neptuneware_storefront", "shopify", "woocommerce"
            $table->string('source', 50)->nullable();

            // status lifecycle (pending, paid, fulfilled, cancelled, refunded, etc.)
            $table->string('status', 40)->default('pending');

            $table->string('currency', 10)->nullable();

            // Totals (storefront-calculated). Keep as integer cents if you prefer; here decimal(12,2).
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            // Customer snapshot (do not depend on CRM companies/contacts at ingest time)
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            // Address snapshots
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            // When the customer placed the order in ecommerce
            $table->timestamp('placed_at')->nullable();

            // Full inbound payload (for audit/debug) + extra metadata
            $table->json('raw_payload')->nullable();
            $table->json('meta')->nullable();

            // Track last inbound update time (useful for reconciliation)
            $table->timestamp('external_updated_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'external_order_id'], 'ecomm_orders_tenant_external_unique');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_orders');
    }
};
