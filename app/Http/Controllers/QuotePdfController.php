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
    protected function resolveLocalLogoPath(Tenant $tenant): ?string
    {
        if (empty($tenant->logo_path)) {
            return null;
        }

        $disk = (string) config('filesystems.tenant_logo_disk', 'tenant_logos');

        try {
            $bytes = Storage::disk($disk)->get($tenant->logo_path);
            if (!$bytes) return null;

            $tmpDir = storage_path('app/tmp');
            if (!is_dir($tmpDir)) {
                @mkdir($tmpDir, 0775, true);
            }
            if (!is_writable($tmpDir)) {
                Log::warning('PDF tmp dir not writable', ['tmp' => $tmpDir]);
                return null;
            }

            $ext = pathinfo($tenant->logo_path, PATHINFO_EXTENSION) ?: 'png';

            // ✅ unique per request (avoids collisions)
            $localPath = $tmpDir . '/tenant_logo_' . $tenant->id . '_' . uniqid('', true) . '.' . $ext;

            file_put_contents($localPath, $bytes);

            return $localPath;
        } catch (\Throwable $e) {
            Log::warning('PDF logo fetch failed: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'disk' => $disk,
                'path' => $tenant->logo_path,
            ]);
            return null;
        }
    }

    public function stream(Tenant $tenant, Quote $quote)
    {
        // ✅ give DomPDF breathing room
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

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

        Log::info('PDF stream start', ['quote_id' => $quote->id, 'tenant_id' => $tenant->id]);

        try {
            $pdf = Pdf::loadView('tenant.quotes.pdf', compact('tenant', 'quote', 'pdfLogoPath'));
            Log::info('PDF stream rendered', ['quote_id' => $quote->id]);

            return $pdf->stream($quote->quote_number . '.pdf');
        } finally {
            if ($pdfLogoPath && file_exists($pdfLogoPath)) {
                @unlink($pdfLogoPath);
            }
        }
    }

    public function download(Tenant $tenant, Quote $quote)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

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

        Log::info('PDF download start', ['quote_id' => $quote->id, 'tenant_id' => $tenant->id]);

        try {
            $pdf = Pdf::loadView('tenant.quotes.pdf', compact('tenant', 'quote', 'pdfLogoPath'));
            Log::info('PDF download rendered', ['quote_id' => $quote->id]);

            return $pdf->download($quote->quote_number . '.pdf');
        } finally {
            if ($pdfLogoPath && file_exists($pdfLogoPath)) {
                @unlink($pdfLogoPath);
            }
        }
    }
}

