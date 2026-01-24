<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->string('sku', 64)->nullable()->after('product_id');
            $table->string('unit', 30)->nullable()->after('sku'); // e.g. pcs, m, box
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn(['sku', 'unit']);
        });
    }
};


