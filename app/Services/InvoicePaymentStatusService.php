<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoicePaymentStatusService
{
    public function sync(int $tenantId, int $invoiceId): void
    {
        DB::transaction(function () use ($tenantId, $invoiceId) {

            $invoice = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            $dirty = false;

            // Only for real invoices (ignore drafts/void if you want)
            if (in_array($invoice->status, ['draft', 'void'], true)) {
                // your choice: force unpaid for drafts/void
                if ($invoice->payment_status !== 'unpaid') {
                    $invoice->payment_status = 'unpaid';
                    $dirty = true;
                }

                // keep paid_at consistent if column exists
                if ($invoice->isFillable('paid_at') && $invoice->paid_at !== null) {
                    $invoice->paid_at = null;
                    $dirty = true;
                }

                if ($dirty) $invoice->save();
                return;
            }

            $applied = (float) DB::table('transaction_allocations')
                ->where('tenant_id', $tenantId)
                ->where('invoice_id', $invoiceId)
                ->sum('amount_applied');

            $applied = round($applied, 2);
            $total   = round((float) $invoice->total, 2);

            $balance = round($total - $applied, 2);

            // avoid "0.01" leftovers caused by rounding
            $tolerance = 0.01;
            if ($balance <= $tolerance) $balance = 0.00;
            if ($applied < 0) $applied = 0.00;

            $paymentStatus =
                ($applied <= 0.00) ? 'unpaid'
                : (($balance == 0.00) ? 'paid' : 'partially_paid');

            // payment_status
            if ($invoice->payment_status !== $paymentStatus) {
                $invoice->payment_status = $paymentStatus;
                $dirty = true;
            }

            // Optional cached columns if you added them
            if ($invoice->isFillable('amount_paid') && round((float) $invoice->amount_paid, 2) !== $applied) {
                $invoice->amount_paid = $applied;
                $dirty = true;
            }

            if ($invoice->isFillable('balance_due') && round((float) $invoice->balance_due, 2) !== $balance) {
                $invoice->balance_due = $balance;
                $dirty = true;
            }

            // paid_at (ONLY driven by payment_status)
            if ($invoice->isFillable('paid_at')) {
                if ($paymentStatus === 'paid') {
                    if ($invoice->paid_at === null) {
                        // pick the latest allocation date if available, else now()
                        $lastAppliedAt = DB::table('transaction_allocations')
                            ->where('tenant_id', $tenantId)
                            ->where('invoice_id', $invoiceId)
                            ->max('applied_at');

                        $invoice->paid_at = $lastAppliedAt ?: now();
                        $dirty = true;
                    }
                } else {
                    // if not paid, clear paid_at (optional but keeps data consistent)
                    if ($invoice->paid_at !== null) {
                        $invoice->paid_at = null;
                        $dirty = true;
                    }
                }
            }

            if ($dirty) $invoice->save();
        });
    }

    public function syncMany(int $tenantId, array $invoiceIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $invoiceIds)));
        foreach ($ids as $id) {
            $this->sync($tenantId, $id);
        }
    }
}

