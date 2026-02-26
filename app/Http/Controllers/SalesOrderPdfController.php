<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SalesOrderPdfController extends Controller
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

    public function stream(Tenant $tenant, SalesOrder $salesOrder)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');

        $this->authorize('view', $salesOrder);
        abort_unless((int) $salesOrder->tenant_id === (int) $tenant->id, 404);

        $salesOrder->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses',
            'contact',
            'deal',
            'tenant',
        ]);

        app(ActivityLogger::class)->log($tenant->id, 'sales_order.pdf_viewed', $salesOrder, [
            'sales_order_number' => $salesOrder->sales_order_number,
        ]);

        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            Log::info('SalesOrder PDF render start', ['sales_order_id' => $salesOrder->id]);

            $pdf = Pdf::loadView('tenant.sales_orders.pdf', compact('tenant', 'salesOrder', 'pdfLogoPath'))
                ->setPaper('a4');

            $bytes = $pdf->output();

            Log::info('SalesOrder PDF render done', [
                'sales_order_id' => $salesOrder->id,
                'bytes' => strlen($bytes),
                'logo_used' => (bool) $pdfLogoPath,
            ]);

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $salesOrder->sales_order_number . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('SalesOrder PDF render failed: ' . $e->getMessage(), [
                'sales_order_id' => $salesOrder->id,
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

    public function download(Tenant $tenant, SalesOrder $salesOrder)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(180);

        $tenant = app('tenant');

        $this->authorize('view', $salesOrder);
        abort_unless((int) $salesOrder->tenant_id === (int) $tenant->id, 404);

        $salesOrder->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'company.addresses',
            'contact',
            'deal',
            'tenant',
        ]);

        app(ActivityLogger::class)->log($tenant->id, 'sales_order.pdf_downloaded', $salesOrder, [
            'sales_order_number' => $salesOrder->sales_order_number,
        ]);

        $pdfLogoPath = $this->resolveLocalLogoPath($tenant);

        try {
            Log::info('SalesOrder PDF download render start', ['sales_order_id' => $salesOrder->id]);

            $pdf = Pdf::loadView('tenant.sales_orders.pdf', compact('tenant', 'salesOrder', 'pdfLogoPath'))
                ->setPaper('a4');

            $bytes = $pdf->output();

            Log::info('SalesOrder PDF download render done', [
                'sales_order_id' => $salesOrder->id,
                'bytes' => strlen($bytes),
                'logo_used' => (bool) $pdfLogoPath,
            ]);

            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $salesOrder->sales_order_number . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('SalesOrder PDF download render failed: ' . $e->getMessage(), [
                'sales_order_id' => $salesOrder->id,
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