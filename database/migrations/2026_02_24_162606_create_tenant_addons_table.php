<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('key', 80)->index();     // e.g. ecommerce
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamp('enabled_at')->nullable();
            $table->unsignedBigInteger('enabled_by_user_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_addons');
    }
};
