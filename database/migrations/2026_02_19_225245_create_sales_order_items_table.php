<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('sales_order_id')->index();

            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('sku', 64)->nullable();
            $table->string('unit', 30)->nullable();

            $table->unsignedBigInteger('tax_type_id')->nullable()->index();

            $table->integer('position')->default(0);

            $table->string('name', 190);
            $table->text('description')->nullable();

            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);

            $table->decimal('discount_pct', 6, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);

            $table->string('tax_name', 100)->nullable();
            $table->decimal('tax_rate', 6, 2)->default(0);

            $table->decimal('line_total', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);

            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};

