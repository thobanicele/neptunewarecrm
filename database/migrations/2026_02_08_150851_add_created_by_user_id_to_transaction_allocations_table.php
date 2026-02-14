<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) add column only if missing
        Schema::table('transaction_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_allocations', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('applied_at');
            }
        });

        // 2) add index only if missing (by column-pair, not only name)
        $hasIndex = DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'transaction_allocations')
            ->whereIn('column_name', ['tenant_id', 'created_by_user_id'])
            ->select('index_name')
            ->groupBy('index_name')
            ->havingRaw('COUNT(DISTINCT column_name) = 2')
            ->exists();

        if (!$hasIndex) {
            Schema::table('transaction_allocations', function (Blueprint $table) {
                $table->index(['tenant_id', 'created_by_user_id'], 'txn_alloc_tenant_createdby_idx');
            });
        }
    }

    public function down(): void
    {
        // drop index if exists (best-effort)
        try {
            Schema::table('transaction_allocations', function (Blueprint $table) {
                $table->dropIndex('txn_alloc_tenant_createdby_idx');
            });
        } catch (\Throwable $e) {
            // ignore if already dropped / never existed
        }

        // drop column if exists
        Schema::table('transaction_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_allocations', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
        });
    }
};
