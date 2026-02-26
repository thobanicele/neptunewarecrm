<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\TransactionAllocation;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoicePdfController extends Controller
{
    protected function resolveLocalLogoPath(Tenant $tenant): ?string
    {
        if (empty($tenant->logo_path)) {
            return null;
        }

        $disk = (string) config('filesystems.tenant_logo_disk', 'tenant_logos');

        try {
            $bytes = Storage::disk($disk)->get($tenant->logo_path);
            if (!$bytes) return null;

            // Guard against huge uploads killing DomPDF
            if (strlen($bytes) > 2 * 1024 * 1024) { // 2MB
                Log::warning('PDF logo too large, skipping', [
                    'tenant_id' => $tenant->id,
                    'path' => $tenant->logo_path,
                    'size' => strlen($bytes),
                ]);
                return null;
            }

            $tmpDir = storage_path('app/tmp');
            if (!is_dir($tmpDir)) {
                @mkdir($tmpDir, 0775, true);
            }
            if (!is_writable($tmpDir)) {
                Log::warning('PDF tmp dir not writable', ['tmp' => $tmpDir]);
                return null;
            }

            $ext = pathinfo($tenant->logo_path, PATHINFO_EXTENSION) ?: 'png';
            $localPath = $tmpDir . '/tenant_logo_' . $tenant->id . '_' . uniqid('', true) . '.' . $ext;

            file_put_contents($localPath, $bytes);

            return $localPath;
        } catch (\Throwable $e) {
            Log::warning('PDF logo fetch failed: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'disk' => $disk,
                'path' => $tenant->logo_path,
                'class' => get_class($e),
                'prev' => $e->getPrevious()?->getMessage(),
            ]);

            return null;
        }
    }

    public function stream(Tenant $tenant, Invoice $invoice)
    {
        $this->authorize('pdf', $invoice);

        $t = app('tenant');
        if ($t && (int) $invoice->tenant_id === (int) $t->id) {
            app(ActivityLogger::class)->log($t->id, 'invoice.pdf_viewed', $invoice, [
                'invoice_number' => $invoice->invoice_number,
            ]);
        }

        return $this->render($tenant, $invoice, 'stream');
    }

    public function download(Tenant $tenant, Invoice $invoice)
    {
        $this->authorize('pdf', $invoice);

        $t = app('tenant');
        if ($t && (int) $invoice->tenant_id === (int) $t->id) {
            app(ActivityLogger::class)->log($t->id, 'invoice.pdf_downloaded', $invoice, [
                'invoice_number' => $invoice->invoice_number,
            ]);
        }

        return $this->render($tenant, $invoice, 'download');
    }

    private function render(Tenant $tenantParam, Invoice $invoice, string $mode)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');
        abort_unless((int) $invoice->tenant_id === (int) $tenant->id, 404);

        $invoice->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses',
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
        // Status update (issued / partially_paid / paid)
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

        // ✅ local temp logo for DomPDF
        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            Log::info('Invoice PDF render start', ['invoice_id' => $invoice->id]);

            $pdf = Pdf::loadView('tenant.invoices.pdf', [
                'tenant'          => $tenant,
                'invoice'         => $invoice,
                'watermark'       => $watermark,

                // totals
                'allocations'     => $allocations,
                'invoiceTotal'    => $invoiceTotal,
                'paymentsApplied' => $paymentsApplied,
                'creditsApplied'  => $creditsApplied,
                'appliedTotal'    => $appliedTotal,
                'balanceDue'      => $balanceDue,

                // ✅ pass logo path
                'pdfLogoPath'     => $pdfLogoPath,
            ])->setPaper('a4');

            $bytes = $pdf->output();

            Log::info('Invoice PDF render done', [
                'invoice_id' => $invoice->id,
                'bytes' => strlen($bytes),
                'logo_used' => (bool) $pdfLogoPath,
            ]);

            $filename = $invoice->invoice_number . '.pdf';

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($mode === 'download' ? 'attachment' : 'inline') . '; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Invoice PDF render failed: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
                'class' => get_class($e),
                'prev' => $e->getPrevious()?->getMessage(),
            ]);

            abort(500, 'PDF generation failed.');
        } finally {
            if ($pdfLogoPath && file_exists($pdfLogoPath)) {
                @unlink($pdfLogoPath);
            }
        }
    }
}