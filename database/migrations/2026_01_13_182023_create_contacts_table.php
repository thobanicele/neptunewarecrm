<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Option B logic
            $table->string('lifecycle_stage')->default('lead'); // lead|qualified|customer
            $table->string('lead_stage')->default('new'); // new|contacted|qualified|converted|lost

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'lifecycle_stage', 'lead_stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

