<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_invites', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->string('email')->index();

            // role to assign when accepted: tenant_admin / tenant_staff (avoid tenant_owner via invites)
            $table->string('role')->default('tenant_staff');

            // store token HASHED for safety
            $table->string('token_hash', 64)->unique();

            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable()->index();

            // who invited
            $table->unsignedBigInteger('invited_by')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();

            // optional: prevent duplicates (same email invited multiple times)
            // we still allow resend by re-issuing token and updating row
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invites');
    }
};

