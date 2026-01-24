<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('billing_address_id')->nullable()->constrained('company_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('company_addresses')->nullOnDelete();

            $table->text('billing_address_snapshot')->nullable();
            $table->text('shipping_address_snapshot')->nullable();
        });
    }
    public function down(): void {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_address_id');
            $table->dropConstrainedForeignId('shipping_address_id');
            $table->dropColumn(['billing_address_snapshot', 'shipping_address_snapshot']);
        });
    }
};

