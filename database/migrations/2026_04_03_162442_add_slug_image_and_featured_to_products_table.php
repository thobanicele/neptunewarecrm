<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('image_path')->nullable()->after('description');
            $table->boolean('is_featured')->default(false)->after('is_active');

            $table->index(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'slug']);
            $table->dropIndex(['tenant_id', 'is_featured']);

            $table->dropColumn([
                'slug',
                'image_path',
                'is_featured',
            ]);
        });
    }
};
