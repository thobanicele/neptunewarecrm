<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('credit_note_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('tax_type_id')->nullable();

            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->text('description')->nullable();

            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount_pct', 8, 2)->default(0);

            // stored line totals (helps PDFs + audits)
            $table->decimal('line_subtotal', 12, 2)->default(0);     // qty*rate
            $table->decimal('line_discount', 12, 2)->default(0);     // subtotal*disc%
            $table->decimal('line_tax', 12, 2)->default(0);          // (subtotal-discount)*tax%
            $table->decimal('line_total', 12, 2)->default(0);        // excl
            $table->decimal('line_total_incl', 12, 2)->default(0);   // incl

            $table->timestamps();

            $table->index(['tenant_id', 'credit_note_id']);
            $table->index(['tenant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
    }
};

