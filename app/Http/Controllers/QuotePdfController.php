<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotePdfController extends Controller
{
    protected function resolveLocalLogoPath(Tenant $tenant): ?string
    {
        if (empty($tenant->logo_path)) {
            return null;
        }

        $disk = (string) config('filesystems.tenant_logo_disk', 'tenant_logos');

        try {
            $storage = Storage::disk($disk);

            // Avoid exists() (HEAD) - just try to get
            $bytes = $storage->get($tenant->logo_path);

            if (!$bytes) {
                return null;
            }

            $ext = pathinfo($tenant->logo_path, PATHINFO_EXTENSION) ?: 'png';
            $tmp = storage_path('app/tmp');

            if (!is_dir($tmp)) {
                @mkdir($tmp, 0775, true);
            }

            $localPath = $tmp . '/tenant_logo_' . $tenant->id . '.' . $ext;

            file_put_contents($localPath, $bytes);

            return $localPath;
        } catch (\Throwable $e) {
            // Don’t break PDF if logo fetch fails
            \Log::warning('PDF logo fetch failed', [
                'tenant_id' => $tenant->id,
                'disk' => $disk,
                'path' => $tenant->logo_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function stream(Tenant $tenant, Quote $quote)
    {
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
            return Pdf::loadView('tenant.quotes.pdf', compact('tenant', 'quote', 'pdfLogoPath'))
                ->stream($quote->quote_number . '.pdf');
        } finally {
            if ($pdfLogoPath && file_exists($pdfLogoPath)) {
                @unlink($pdfLogoPath);
            }
        }
    }

    public function download(Tenant $tenant, Quote $quote)
    {
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
            return Pdf::loadView('tenant.quotes.pdf', compact('tenant', 'quote', 'pdfLogoPath'))
                ->download($quote->quote_number . '.pdf');
        } finally {
            if ($pdfLogoPath && file_exists($pdfLogoPath)) {
                @unlink($pdfLogoPath);
            }
        }
    }
}


