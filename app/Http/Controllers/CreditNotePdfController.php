<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreditNotePdfController extends Controller
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
                Log::warning('CreditNote PDF logo too large, skipping', [
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
                Log::warning('CreditNote PDF tmp dir not writable', ['tmp' => $tmpDir]);
                return null;
            }

            $ext = pathinfo($tenant->logo_path, PATHINFO_EXTENSION) ?: 'png';
            $localPath = $tmpDir . '/tenant_logo_' . $tenant->id . '_' . uniqid('', true) . '.' . $ext;

            file_put_contents($localPath, $bytes);

            return $localPath;
        } catch (\Throwable $e) {
            Log::warning('CreditNote PDF logo fetch failed: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'disk' => $disk,
                'path' => $tenant->logo_path,
                'class' => get_class($e),
                'prev' => $e->getPrevious()?->getMessage(),
            ]);
            return null;
        }
    }

    public function stream(Request $request, string $tenantKey, CreditNote $creditNote)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');
        abort_unless((int) $creditNote->tenant_id === (int) $tenant->id, 404);

        $creditNote->load([
            'company.addresses',
            'contact',
            'items.taxType',
        ]);

        $billTo = $this->companyBillingText($creditNote);
        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            Log::info('CreditNote PDF render start', ['credit_note_id' => $creditNote->id]);

            $pdf = Pdf::loadView('tenant.credit_notes.pdf', [
                'tenant' => $tenant,
                'creditNote' => $creditNote,
                'billTo' => $billTo,
                'pdfLogoPath' => $pdfLogoPath,
            ])->setPaper('a4');

            $bytes = $pdf->output();

            Log::info('CreditNote PDF render done', [
                'credit_note_id' => $creditNote->id,
                'bytes' => strlen($bytes),
                'logo_used' => (bool) $pdfLogoPath,
            ]);

            $filename = 'Credit-Note-' . ($creditNote->credit_note_number ?? $creditNote->id) . '.pdf';

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            Log::error('CreditNote PDF render failed: ' . $e->getMessage(), [
                'credit_note_id' => $creditNote->id,
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

    public function download(Request $request, string $tenantKey, CreditNote $creditNote)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');
        abort_unless((int) $creditNote->tenant_id === (int) $tenant->id, 404);

        $creditNote->load([
            'company.addresses',
            'contact',
            'items.taxType',
        ]);

        $billTo = $this->companyBillingText($creditNote);
        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            Log::info('CreditNote PDF download render start', ['credit_note_id' => $creditNote->id]);

            $pdf = Pdf::loadView('tenant.credit_notes.pdf', [
                'tenant' => $tenant,
                'creditNote' => $creditNote,
                'billTo' => $billTo,
                'pdfLogoPath' => $pdfLogoPath,
            ])->setPaper('a4');

            $bytes = $pdf->output();

            Log::info('CreditNote PDF download render done', [
                'credit_note_id' => $creditNote->id,
                'bytes' => strlen($bytes),
                'logo_used' => (bool) $pdfLogoPath,
            ]);

            $filename = 'Credit-Note-' . ($creditNote->credit_note_number ?? $creditNote->id) . '.pdf';

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            Log::error('CreditNote PDF download render failed: ' . $e->getMessage(), [
                'credit_note_id' => $creditNote->id,
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

    private function companyBillingText(CreditNote $creditNote): string
    {
        $company = $creditNote->company;
        if (!$company) return '';

        if (method_exists($company, 'addresses') && $company->relationLoaded('addresses') === false) {
            $company->load('addresses.country', 'addresses.subdivision');
        }

        if (!method_exists($company, 'addresses')) {
            return '';
        }

        $billing =
            $company->addresses->firstWhere('is_default_billing', 1) ??
            $company->addresses->firstWhere('type', 'billing') ??
            $company->addresses->first();

        if (!$billing) return '';

        $parts = array_filter([
            $billing->label ?: null,
            $billing->attention ?: null,
            $billing->line1 ?: null,
            $billing->line2 ?: null,
            $billing->city ?: null,
            $billing->subdivision_text ?: (optional($billing->subdivision)->name ?? null),
            $billing->postal_code ?: null,
            optional($billing->country)->name ?? null,
        ]);

        return trim(implode("\n", $parts));
    }
}

