<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class TenantBrandingLogoController extends Controller
{
    public function show(Tenant $tenant)
    {
        $ctx = app('tenant');

        abort_unless($ctx && (int) $ctx->id === (int) $tenant->id, 404);
        abort_unless(!empty($tenant->logo_path), 404);

        $disk = (string) config('filesystems.tenant_logo_disk', 'tenant_logos');

        try {
            $storage = Storage::disk($disk);

            // ✅ Avoid exists() (HEAD) — it can fail on some S3-compatible configs
            $stream = $storage->readStream($tenant->logo_path);
            abort_unless($stream !== false, 404);

            $mime = $storage->mimeType($tenant->logo_path) ?: 'image/png';

            return response()->stream(function () use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=3600',
            ]);
        } catch (\Throwable $e) {
            $prev = $e->getPrevious();

            \Log::error('Branding logo fetch failed: ' . $e->getMessage(), [
                'tenant_id' => $tenant->id,
                'disk' => $disk,
                'path' => $tenant->logo_path,
                'class' => get_class($e),
                'prev_class' => $prev ? get_class($prev) : null,
                'prev_message' => $prev ? $prev->getMessage() : null,
            ]);

            abort(404);
        }
    }
}
