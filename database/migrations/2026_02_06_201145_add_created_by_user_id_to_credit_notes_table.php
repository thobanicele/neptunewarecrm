<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) add column only if missing
        Schema::table('credit_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('credit_notes', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('notes');
            }
        });

        // 2) add index only if missing
        $indexName = 'credit_notes_tenant_id_created_by_user_id_index';

        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'credit_notes')
            ->where('index_name', $indexName)
            ->exists();

        if (!$exists) {
            Schema::table('credit_notes', function (Blueprint $table) use ($indexName) {
                $table->index(['tenant_id', 'created_by_user_id'], $indexName);
            });
        }
    }

    public function down(): void
    {
        $indexName = 'credit_notes_tenant_id_created_by_user_id_index';

        // drop index if it exists (best-effort), then drop column if exists
        try {
            Schema::table('credit_notes', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Throwable $e) {
            // ignore if already dropped / never existed
        }

        Schema::table('credit_notes', function (Blueprint $table) {
            if (Schema::hasColumn('credit_notes', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
        });
    }
};


