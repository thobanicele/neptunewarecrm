<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_storefront_visible')->default(false)->after('is_featured');
            $table->index(['tenant_id', 'is_storefront_visible']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'is_storefront_visible']);
            $table->dropColumn('is_storefront_visible');
        });
    }
};
