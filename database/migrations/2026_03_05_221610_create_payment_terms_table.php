<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');

            $table->string('name');
            $table->string('name_normalized');
            $table->unsignedInteger('days'); // required always

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);

            // unique per tenant
            $table->unique(['tenant_id', 'name_normalized']);
            $table->unique(['tenant_id', 'days']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};