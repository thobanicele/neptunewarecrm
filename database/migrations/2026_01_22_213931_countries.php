<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('iso2', 2)->unique();          // ZA
            $table->char('iso3', 3)->nullable();        // ZAF
            $table->string('name');
            $table->string('numeric_code', 3)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('countries');
    }
};

