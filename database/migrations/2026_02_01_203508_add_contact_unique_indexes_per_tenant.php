<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Enforce uniqueness per tenant
            $table->unique(['tenant_id', 'email'], 'contacts_tenant_email_unique');
            $table->unique(['tenant_id', 'phone'], 'contacts_tenant_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique('contacts_tenant_email_unique');
            $table->dropUnique('contacts_tenant_phone_unique');
        });
    }
};

