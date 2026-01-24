<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Customer PO / Reference
            $table->string('customer_reference', 120)->nullable()->after('quote_number');

            // Total discount amount (sum of line discounts) in currency
            $table->decimal('discount_amount', 14, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['customer_reference', 'discount_amount']);
        });
    }
};

