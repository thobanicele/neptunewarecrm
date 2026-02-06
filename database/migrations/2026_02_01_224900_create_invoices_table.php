<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // Source quote (optional for manual invoices)
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->unique(['tenant_id', 'quote_id']); // prevents double conversion (NULLs allowed)

            // Numbering
            $table->string('invoice_number', 50);
            $table->unique(['tenant_id', 'invoice_number']);

            // Quote snapshot + editable reference
            $table->string('quote_number', 50)->nullable(); // snapshot
            $table->string('reference', 120)->nullable();   // editable, defaults to quote_number

            // Links
            $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();

            // Carry over staff fields
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sales_person_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Status & dates
            $table->string('status', 20)->default('draft'); // draft|issued|paid|void
            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            // Tax & totals (mirror quote pattern)
            $table->foreignId('tax_type_id')->nullable()->constrained('tax_types')->nullOnDelete();
            $table->string('currency', 10)->default('ZAR');

            $table->decimal('subtotal', 14, 2)->default(0.00);         // GROSS before discount
            $table->decimal('discount_amount', 14, 2)->default(0.00);  // header discount total
            $table->decimal('tax_rate', 5, 2)->default(15.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('total', 14, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            // Address snapshots (audit/SARS-friendly)
            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->text('billing_address_snapshot')->nullable();
            $table->text('shipping_address_snapshot')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

