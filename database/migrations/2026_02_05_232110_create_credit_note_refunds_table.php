<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_note_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('credit_note_id');

            $table->date('refunded_at')->nullable();
            $table->decimal('amount', 14, 2)->default(0);

            $table->string('method')->nullable();     // cash/eft/card etc
            $table->string('reference')->nullable();  // proof / ref #
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by_user_id')->nullable();

            $table->timestamps();

            $table->index(['tenant_id','company_id']);
            $table->index(['tenant_id','credit_note_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_refunds');
    }
};

