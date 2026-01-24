<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('vat_number', 64)->nullable()->after('name');
            $table->string('vat_treatment', 50)->nullable()->after('vat_number'); // optional
        });
    }
    public function down(): void {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['vat_number', 'vat_treatment']);
        });
    }
};

