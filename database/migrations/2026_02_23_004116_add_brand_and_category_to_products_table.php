<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('brands')
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('categories')
                ->nullOnDelete();

            $table->index(['tenant_id', 'brand_id']);
            $table->index(['tenant_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'brand_id']);
            $table->dropIndex(['tenant_id', 'category_id']);

            $table->dropConstrainedForeignId('brand_id');
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
