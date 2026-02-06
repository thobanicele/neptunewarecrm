<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->timestamp('invoiced_at')->nullable()->after('declined_at');
            $table->unsignedBigInteger('invoice_id')->nullable()->after('invoiced_at');
            $table->index(['tenant_id','invoiced_at']);
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['tenant_id','invoiced_at']);
            $table->dropColumn(['invoiced_at','invoice_id']);
        });
    }
};


