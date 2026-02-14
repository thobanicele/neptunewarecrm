<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) add column only if missing
        Schema::table('credit_note_refunds', function (Blueprint $table) {
            if (!Schema::hasColumn('credit_note_refunds', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable();
            }
        });

        // 2) add index only if missing (check by columns, not only by name)
        $hasIndex = DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'credit_note_refunds')
            ->whereIn('column_name', ['tenant_id', 'created_by_user_id'])
            ->select('index_name')
            ->groupBy('index_name')
            ->havingRaw('COUNT(DISTINCT column_name) = 2')
            ->exists();

        if (!$hasIndex) {
            Schema::table('credit_note_refunds', function (Blueprint $table) {
                $table->index(['tenant_id', 'created_by_user_id'], 'cn_refunds_tenant_createdby_idx');
            });
        }
    }

    public function down(): void
    {
        // drop index if exists (best-effort)
        try {
            Schema::table('credit_note_refunds', function (Blueprint $table) {
                $table->dropIndex('cn_refunds_tenant_createdby_idx');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('credit_note_refunds', function (Blueprint $table) {
            if (Schema::hasColumn('credit_note_refunds', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
        });
    }
};

