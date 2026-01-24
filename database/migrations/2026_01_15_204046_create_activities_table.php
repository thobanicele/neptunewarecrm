<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphic subject: Deal or Contact (Lead)
            $table->morphs('subject'); // subject_type, subject_id

            $table->string('type', 30); // call|meeting|email|note|task (optional)
            $table->string('title')->nullable();
            $table->text('body')->nullable();

            $table->timestamp('due_at')->nullable();
            $table->timestamp('done_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id']);
            $table->index(['tenant_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

