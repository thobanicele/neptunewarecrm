<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('quote_id')->nullable()->index();
            $table->string('sales_order_number', 50)->index();
            $table->string('quote_number', 50)->nullable()->index();
            $table->string('reference', 120)->nullable();

            $table->unsignedBigInteger('deal_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('contact_id')->nullable()->index();
            $table->unsignedBigInteger('owner_user_id')->nullable()->index();
            $table->unsignedBigInteger('sales_person_user_id')->nullable()->index();

            $table->string('status', 20)->default('draft'); // draft|issued|cancelled|converted
            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();

            $table->unsignedBigInteger('tax_type_id')->nullable()->index();

            $table->string('currency', 10)->default('ZAR');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_rate', 6, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            // keep same style as invoices (optional)
            $table->unsignedBigInteger('billing_address_id')->nullable()->index();
            $table->unsignedBigInteger('shipping_address_id')->nullable()->index();
            $table->text('billing_address_snapshot')->nullable();
            $table->text('shipping_address_snapshot')->nullable();

            $table->timestamps();

            // Uniqueness per tenant
            $table->unique(['tenant_id', 'sales_order_number'], 'so_tenant_number_unique');

            // FKs (optional strictness; keep consistent with your DB style)
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('quote_id')->references('id')->on('quotes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};

