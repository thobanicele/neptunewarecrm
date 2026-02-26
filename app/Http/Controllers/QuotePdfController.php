<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QuotePdfController extends Controller
{
    /**
     * Download tenant logo from tenant_logos (R2/S3) to a local temp file for DomPDF.
     * Returns absolute local path or null.
     */
    protected function resolveLocalLogoPath(Tenant $tenant): ?string
    {
        if (empty($tenant->logo_path)) {
            return null;
        }

        $disk = (string) config('filesystems.tenant_logo_disk', 'tenant_logos');

        try {
            // Avoid exists() (HEAD) - just try to GET
            $bytes = Storage::disk($disk)->get($tenant->logo_path);

            if (!$bytes) {
                return null;
            }

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

            // Unique per request to avoid collisions
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

    public function stream(Tenant $tenant, Quote $quote)
    {
        // Give DomPDF enough resources
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');
        $this->authorize('view', $quote);
        abort_unless((int) $quote->tenant_id === (int) $tenant->id, 404);

        $quote->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses',
            'contact',
            'deal',
            'tenant',
        ]);

        app(ActivityLogger::class)->log($tenant->id, 'quote.pdf_viewed', $quote, [
            'quote_number' => $quote->quote_number,
        ]);

        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            Log::info('PDF render start', ['quote_id' => $quote->id, 'tenant_id' => $tenant->id]);

            $pdf = Pdf::loadView('tenant.quotes.pdf', compact('tenant', 'quote', 'pdfLogoPath'))
                ->setPaper('a4');

            // Render now so temp files exist during render (avoids 502 streaming crashes)
            $bytes = $pdf->output();

            Log::info('PDF render done', [
                'quote_id' => $quote->id,
                'bytes' => strlen($bytes),
                'logo_used' => (bool) $pdfLogoPath,
            ]);

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $quote->quote_number . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('PDF render failed: ' . $e->getMessage(), [
                'quote_id' => $quote->id,
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

    public function download(Tenant $tenant, Quote $quote)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');
        $this->authorize('view', $quote);
        abort_unless((int) $quote->tenant_id === (int) $tenant->id, 404);

        $quote->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses',
            'contact',
            'deal',
            'tenant',
        ]);

        app(ActivityLogger::class)->log($tenant->id, 'quote.pdf_downloaded', $quote, [
            'quote_number' => $quote->quote_number,
        ]);

        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            Log::info('PDF download render start', ['quote_id' => $quote->id, 'tenant_id' => $tenant->id]);

            $pdf = Pdf::loadView('tenant.quotes.pdf', compact('tenant', 'quote', 'pdfLogoPath'))
                ->setPaper('a4');

            $bytes = $pdf->output();

            Log::info('PDF download render done', [
                'quote_id' => $quote->id,
                'bytes' => strlen($bytes),
                'logo_used' => (bool) $pdfLogoPath,
            ]);

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $quote->quote_number . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('PDF download render failed: ' . $e->getMessage(), [
                'quote_id' => $quote->id,
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

