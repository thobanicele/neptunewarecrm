<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->string('sku', 64)->nullable();
            $table->string('unit', 30)->nullable();

            $table->foreignId('tax_type_id')->nullable()->constrained('tax_types')->nullOnDelete();

            $table->unsignedInteger('position')->default(0);

            $table->string('name', 190);
            $table->text('description')->nullable();

            $table->decimal('qty', 12, 2)->default(1.00);
            $table->decimal('unit_price', 14, 2)->default(0.00);

            $table->decimal('discount_pct', 5, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);

            $table->decimal('tax_rate', 6, 2)->default(0.00);
            $table->string('tax_name', 100)->nullable();

            $table->decimal('line_total', 14, 2)->default(0.00); // NET excl VAT
            $table->decimal('tax_amount', 14, 2)->default(0.00);

            $table->timestamps();

            $table->index(['tenant_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};

