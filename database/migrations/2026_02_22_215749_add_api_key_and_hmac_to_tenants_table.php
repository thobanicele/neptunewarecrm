<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Put after subdomain if it exists; otherwise just add normally
            if (Schema::hasColumn('tenants', 'subdomain')) {
                $table->string('api_key', 80)->nullable()->unique()->after('subdomain');
                $table->string('api_hmac_secret', 120)->nullable()->after('api_key');
            } else {
                $table->string('api_key', 80)->nullable()->unique();
                $table->string('api_hmac_secret', 120)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['api_key']);
            $table->dropColumn(['api_key', 'api_hmac_secret']);
        });
    }
};
