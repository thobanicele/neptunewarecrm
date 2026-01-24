<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            if (!Schema::hasColumn('quote_items', 'tax_type_id')) {
                $table->unsignedBigInteger('tax_type_id')->nullable()->index()->after('product_id');
            }
            if (!Schema::hasColumn('quote_items', 'tax_name')) {
                $table->string('tax_name', 100)->nullable()->after('description');
            }
            if (!Schema::hasColumn('quote_items', 'tax_rate')) {
                $table->decimal('tax_rate', 6, 2)->default(0)->after('tax_name'); // snapshot %
            }
            if (!Schema::hasColumn('quote_items', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 2)->default(0)->after('line_total'); // snapshot value
            }

            // Optional FK (only if you want constraints)
            // $table->foreign('tax_type_id')->references('id')->on('tax_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            if (Schema::hasColumn('quote_items', 'tax_amount')) $table->dropColumn('tax_amount');
            if (Schema::hasColumn('quote_items', 'tax_rate')) $table->dropColumn('tax_rate');
            if (Schema::hasColumn('quote_items', 'tax_name')) $table->dropColumn('tax_name');
            if (Schema::hasColumn('quote_items', 'tax_type_id')) $table->dropColumn('tax_type_id');
        });
    }
};

