<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // paystack
            $table->string('cycle');    // monthly|yearly
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('ZAR');
            $table->string('interval'); // monthly|annually (Paystack interval values)
            $table->string('plan_code')->unique();
            $table->timestamps();

            $table->unique(['provider','cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
