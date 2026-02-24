<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) return;

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // what happened
            $table->string('action', 120)->index();

            // polymorphic subject (Quote, Invoice, SalesOrder etc.)
            $table->string('subject_type', 190)->index();
            $table->unsignedBigInteger('subject_id')->index();

            // optional metadata (from/to etc.)
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['tenant_id','subject_type','subject_id'], 'activity_logs_subject_idx');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
