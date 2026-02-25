<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {

            if (!Schema::hasColumn('companies', 'vat_number')) {
                $table->string('vat_number', 64)->nullable()->after('name');
            }

            // If this migration also adds other columns, wrap them too:
            // if (!Schema::hasColumn('companies', 'registration_number')) { ... }
            // if (!Schema::hasColumn('companies', '...')) { ... }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'vat_number')) {
                $table->dropColumn('vat_number');
            }
        });
    }
};

