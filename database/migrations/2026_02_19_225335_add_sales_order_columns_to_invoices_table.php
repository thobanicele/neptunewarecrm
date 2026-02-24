<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'sales_order_id')) {
                $table->unsignedBigInteger('sales_order_id')->nullable()->after('quote_id')->index();
            }
            if (!Schema::hasColumn('invoices', 'sales_order_number')) {
                $table->string('sales_order_number', 50)->nullable()->after('quote_number')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'sales_order_id')) {
                $table->dropColumn('sales_order_id');
            }
            if (Schema::hasColumn('invoices', 'sales_order_number')) {
                $table->dropColumn('sales_order_number');
            }
        });
    }
};

