<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            // Per-line discount percent
            $table->decimal('discount_pct', 5, 2)->default(0)->after('unit_price');

            // Per-line discount amount in currency
            $table->decimal('discount_amount', 14, 2)->default(0)->after('discount_pct');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn(['discount_pct', 'discount_amount']);
        });
    }
};

