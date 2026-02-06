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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('invoice_id')->nullable(); // optional link

            $table->string('credit_note_number', 50)->unique();
            $table->date('issued_at')->nullable();

            $table->decimal('amount', 14, 2)->default(0);
            $table->string('reason', 190)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id','company_id','issued_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
