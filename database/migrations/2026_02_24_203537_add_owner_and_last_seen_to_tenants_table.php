<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_user_id')->nullable()->after('id');
            $table->timestamp('last_seen_at')->nullable()->after('status');

            $table->index('owner_user_id');
            $table->index('last_seen_at');

            // Optional FK (safe if users table exists already)
            $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropIndex(['owner_user_id']);
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn(['owner_user_id', 'last_seen_at']);
        });
    }
};
