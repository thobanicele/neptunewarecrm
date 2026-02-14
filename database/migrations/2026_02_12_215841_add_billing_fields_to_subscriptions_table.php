<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('plan');         // 'paystack'
            $table->string('last_payment_ref')->nullable()->after('provider');
            $table->string('plan_cycle')->nullable()->after('last_payment_ref');
            $table->timestamp('trial_ends_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['provider', 'last_payment_ref', 'plan_cycle', 'trial_ends_at']);
        });
    }
};

