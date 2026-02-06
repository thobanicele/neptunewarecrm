<?php

namespace App\Services;

use App\Models\TenantCounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class DocumentNumberService
{
    /**
     * Generate a per-tenant sequential document number safely.
     *
     * Uses tenant_counters table with UNIQUE(tenant_id, key) and row locking.
     */
    public function next(int $tenantId, string $key, string $prefix, int $pad = 6, bool $includeYear = false): string
    {
        $nextValue = DB::transaction(function () use ($tenantId, $key) {

            // 1) Ensure the counter row exists (race-safe via UNIQUE constraint)
            try {
                TenantCounter::firstOrCreate(
                    ['tenant_id' => $tenantId, 'key' => $key],
                    ['value' => 0]
                );
            } catch (QueryException $e) {
                // Another request created it first — ignore and continue
            }

            // 2) Lock row and increment safely
            $counter = TenantCounter::query()
                ->where('tenant_id', $tenantId)
                ->where('key', $key)
                ->lockForUpdate()
                ->firstOrFail();

            $counter->value = (int) $counter->value + 1;
            $counter->save();

            return (int) $counter->value;
        });

        // Build number
        $number = $prefix . '-' . str_pad((string) $nextValue, $pad, '0', STR_PAD_LEFT);

        if ($includeYear) {
            $year = now()->format('Y');
            $number = $prefix . '-' . $year . '-' . str_pad((string) $nextValue, $pad, '0', STR_PAD_LEFT);
        }

        return $number;
    }

    /**
     * ✅ Invoice numbers WITHOUT year:
     * Example: INV-000001
     */
    public function nextInvoiceNumber(int $tenantId): string
    {
        return $this->next($tenantId, 'invoice', 'INV', 6, false);
    }

    /**
     * Quote numbers (choose what you want).
     * If you want: QUO-000001
     */
    public function nextQuoteNumber(int $tenantId): string
    {
        return $this->next($tenantId, 'quote', 'QUO', 5, false);
    }
}

