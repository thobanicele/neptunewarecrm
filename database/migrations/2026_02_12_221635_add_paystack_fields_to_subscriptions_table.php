<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('paystack_plan_code')->nullable()->after('provider'); 
            $table->string('paystack_subscription_code')->nullable()->after('paystack_plan_code');
            $table->string('paystack_email_token')->nullable()->after('paystack_subscription_code');
            $table->string('paystack_customer_code')->nullable()->after('paystack_email_token');
            $table->string('paystack_authorization_code')->nullable()->after('paystack_customer_code');
            $table->timestamp('canceled_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'paystack_plan_code','paystack_subscription_code','paystack_email_token',
                'paystack_customer_code','paystack_authorization_code',
                'canceled_at',
            ]);
        });
    }
};

