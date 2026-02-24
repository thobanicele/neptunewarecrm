<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('external_order_id')
                ->constrained('companies')->nullOnDelete();

            $table->foreignId('contact_id')->nullable()->after('company_id')
                ->constrained('contacts')->nullOnDelete();

            $table->index(['tenant_id','company_id']);
            $table->index(['tenant_id','contact_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id','company_id']);
            $table->dropIndex(['tenant_id','contact_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('contact_id');
        });
    }
};
