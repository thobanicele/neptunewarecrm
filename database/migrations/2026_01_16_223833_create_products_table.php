<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('sku', 80);
            $table->string('name', 190);
            $table->string('description', 255)->nullable();

            $table->decimal('unit_rate', 14, 2)->default(0);

            // common attributes
            $table->string('unit', 30)->nullable();          // each / m / box
            $table->boolean('is_active')->default(true);
            $table->string('currency', 10)->nullable();      // optional
            $table->string('tax_code', 30)->nullable();      // optional

            $table->timestamps();

            // unique per tenant
            $table->unique(['tenant_id', 'sku']);
            $table->unique(['tenant_id', 'name']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};


