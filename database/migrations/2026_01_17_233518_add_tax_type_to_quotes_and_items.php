<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_type_id')->nullable()->after('valid_until');
            // keep your existing quotes.tax_rate, tax_amount etc (we will compute from tax types)
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_type_id')->nullable()->after('product_id');
            $table->decimal('tax_rate', 6, 2)->default(0)->after('unit_price'); // snapshot
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('tax_type_id');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn(['tax_type_id', 'tax_rate']);
        });
    }
};

