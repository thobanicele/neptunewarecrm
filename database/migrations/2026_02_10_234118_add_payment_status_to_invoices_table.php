<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_status')) {
                $table->string('payment_status', 20)->default('unpaid')->after('status');
                $table->index(['tenant_id', 'payment_status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'payment_status')) {
                $table->dropIndex(['tenant_id', 'payment_status']);
                $table->dropColumn('payment_status');
            }
        });
    }
};

