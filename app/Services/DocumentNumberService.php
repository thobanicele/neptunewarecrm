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
    public function next(
        int $tenantId,
        string $key,
        string $prefix,
        int $pad = 6,
        bool $includeYear = false
    ): string {
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

        $padded = str_pad((string) $nextValue, $pad, '0', STR_PAD_LEFT);

        // ✅ If prefix is empty, return just the padded number (e.g. 00001)
        if ($prefix === '') {
            return $padded;
        }

        // With prefix
        if ($includeYear) {
            $year = now()->format('Y');
            return $prefix . '-' . $year . '-' . $padded;
        }

        return $prefix . '-' . $padded;
    }

    /**
     * Invoice numbers WITHOUT year:
     * Example: INV-000001
     */
    public function nextInvoiceNumber(int $tenantId): string
    {
        return $this->next($tenantId, 'invoice', 'INV', 6, false);
    }

    /**
     * Quote numbers WITHOUT year:
     * Example: QUO-00001
     */
    public function nextQuoteNumber(int $tenantId): string
    {
        return $this->next($tenantId, 'quote', 'QUO', 5, false);
    }

    /**
     * ✅ Credit Note numbers WITHOUT year and WITHOUT prefix:
     * Example: 00001
     */
    public function nextCreditNoteNumber(int $tenantId): string
    {
        return $this->next($tenantId, 'credit_note', 'CN', 5, false);
    }
}


