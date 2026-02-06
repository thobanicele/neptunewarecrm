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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('contact_id')->nullable();

            $table->date('paid_at')->nullable(); // payment date
            $table->decimal('amount', 14, 2)->default(0);

            $table->string('method', 30)->nullable(); // eft/cash/card/etc
            $table->string('reference', 120)->nullable(); // bank ref / receipt ref
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id','company_id','paid_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
