<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('country_subdivisions', function (Blueprint $table) {
            // 191 is a good safe length for indexed varchar under utf8mb4
            $table->string('code', 191)->change();
            $table->string('iso_code', 191)->nullable()->change(); // also safe
        });
    }

    public function down(): void
    {
        Schema::table('country_subdivisions', function (Blueprint $table) {
            $table->string('code', 32)->change();
            $table->string('iso_code', 32)->nullable()->change();
        });
    }
};

