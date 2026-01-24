<?php
// database/migrations/xxxx_xx_xx_create_deal_activities_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deal_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type'); // status_change | note
            $table->json('meta')->nullable(); // {from_stage_id,to_stage_id} etc
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'deal_id']);
            $table->index(['deal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_activities');
    }
};



