<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Address blocks (simple text approach like Zoho)
            if (!Schema::hasColumn('companies', 'billing_address')) {
                $table->text('billing_address')->nullable()->after('address');
            }
            if (!Schema::hasColumn('companies', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->after('billing_address');
            }

            // VAT display block
            if (!Schema::hasColumn('companies', 'vat_treatment')) {
                $table->string('vat_treatment', 50)->nullable()->after('shipping_address'); // e.g. VAT Registered
            }
            if (!Schema::hasColumn('companies', 'vat_number')) {
                $table->string('vat_number', 50)->nullable()->after('vat_treatment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'vat_number')) $table->dropColumn('vat_number');
            if (Schema::hasColumn('companies', 'vat_treatment')) $table->dropColumn('vat_treatment');
            if (Schema::hasColumn('companies', 'shipping_address')) $table->dropColumn('shipping_address');
            if (Schema::hasColumn('companies', 'billing_address')) $table->dropColumn('billing_address');
        });
    }
};

