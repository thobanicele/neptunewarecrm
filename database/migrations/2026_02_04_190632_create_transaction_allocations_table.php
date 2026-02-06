<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');

            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('credit_note_id')->nullable();

            $table->decimal('amount_applied', 14, 2)->default(0);
            $table->date('applied_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id','invoice_id']);
            $table->index(['tenant_id','payment_id']);
            $table->index(['tenant_id','credit_note_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_allocations');
    }
};
