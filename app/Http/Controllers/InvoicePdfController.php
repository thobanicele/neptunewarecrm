<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\TransactionAllocation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\Tenant;

class InvoicePdfController extends Controller
{
    public function stream(Tenant $tenant, Invoice $invoice)
    {
        $this->authorize('pdf', $invoice);
        return $this->render($tenant, $invoice, 'stream');
    }

    public function download(Tenant $tenant, Invoice $invoice)
    {
        $this->authorize('pdf', $invoice);
        return $this->render($tenant, $invoice, 'download');
    }

    private function render(Tenant $tenantParam, Invoice $invoice, string $mode)
    {
        $tenant = app('tenant');
        abort_unless((int) $invoice->tenant_id === (int) $tenant->id, 404);

        $invoice->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company',
            'contact',
            'deal',
            'quote',
            'tenant',
        ]);

        // -------------------------
        // Allocations + totals
        // -------------------------
        $allocations = TransactionAllocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('invoice_id', $invoice->id)
            ->orderBy('applied_at')
            ->get();

        $paymentsApplied = (float) $allocations->whereNotNull('payment_id')->sum('amount_applied');
        $creditsApplied  = (float) $allocations->whereNotNull('credit_note_id')->sum('amount_applied');

        // Use your real total field (adjust if needed)
        $invoiceTotal = (float) (
            $invoice->total
            ?? $invoice->grand_total
            ?? $invoice->total_amount
            ?? 0
        );

        $appliedTotal  = $paymentsApplied + $creditsApplied;
        $balanceDueRaw = $invoiceTotal - $appliedTotal;
        $balanceDue    = max(0, $balanceDueRaw);

        // -------------------------
        // Status update (Option A)
        // issued / partially_paid / paid
        // -------------------------
        $newStatus = match (true) {
            $appliedTotal <= 0 => 'issued',
            $balanceDueRaw <= 0 => 'paid',
            default => 'partially_paid',
        };

        if ($invoice->status !== $newStatus) {
            $invoice->forceFill(['status' => $newStatus])->save();
        }

        // Feature flag watermark
        $watermark = tenant_feature($tenant, 'invoice_pdf_watermark');

        $pdf = Pdf::loadView('tenant.invoices.pdf', [
            'tenant'         => $tenant,
            'invoice'        => $invoice,
            'watermark'      => $watermark,

            // pass totals to PDF view
            'allocations'     => $allocations,
            'invoiceTotal'    => $invoiceTotal,
            'paymentsApplied' => $paymentsApplied,
            'creditsApplied'  => $creditsApplied,
            'appliedTotal'    => $appliedTotal,
            'balanceDue'      => $balanceDue,
        ]);

        $filename = $invoice->invoice_number . '.pdf';

        return $mode === 'download'
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}




