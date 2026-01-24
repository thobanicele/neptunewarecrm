<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('country_subdivisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();

            $table->string('code', 32);                 // e.g. GP or ZA-GP depending on dataset
            $table->string('iso_code', 32)->nullable(); // ISO 3166-2 if available
            $table->string('name');
            $table->unsignedTinyInteger('level')->default(1); // 1..3
            $table->string('parent_code', 32)->nullable();

            $table->timestamps();

            $table->unique(['country_id', 'level', 'code']);
            $table->index(['country_id', 'level']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('country_subdivisions');
    }
};

