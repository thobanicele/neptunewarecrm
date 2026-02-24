<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            // Separate states so you can support COD/EFT/manual approvals cleanly
            $table->string('payment_status', 30)->default('pending')->after('status');
            $table->string('fulfillment_status', 30)->default('unfulfilled')->after('payment_status');

            $table->timestamp('paid_at')->nullable()->after('placed_at');
            $table->timestamp('fulfilled_at')->nullable()->after('paid_at');

            // Conversion mapping (idempotent)
            $table->foreignId('converted_sales_order_id')
                ->nullable()
                ->after('fulfilled_at')
                ->constrained('sales_orders')
                ->nullOnDelete();

            $table->timestamp('converted_at')->nullable()->after('converted_sales_order_id');

            $table->index(['tenant_id', 'payment_status']);
            $table->index(['tenant_id', 'fulfillment_status']);
            $table->index(['tenant_id', 'converted_sales_order_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'payment_status']);
            $table->dropIndex(['tenant_id', 'fulfillment_status']);
            $table->dropIndex(['tenant_id', 'converted_sales_order_id']);

            $table->dropConstrainedForeignId('converted_sales_order_id');
            $table->dropColumn([
                'payment_status',
                'fulfillment_status',
                'paid_at',
                'fulfilled_at',
                'converted_at',
            ]);
        });
    }
};
