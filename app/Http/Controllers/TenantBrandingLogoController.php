<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TenantBrandingLogoController extends Controller
{
    public function show(Tenant $tenant)
    {
        // Your IdentifyTenantFromPath middleware sets app('tenant') for access control.
        $ctx = app('tenant');

        // Safety: ensure route tenant matches resolved tenant context
        abort_unless($ctx && (int) $ctx->id === (int) $tenant->id, 404);
        abort_unless(!empty($tenant->logo_path), 404);

        $disk = (string) config('filesystems.tenant_logo_disk', 'tenant_logos');

        try {
            $storage = Storage::disk($disk);

            // exists() can throw on misconfigured S3/R2; don't 500 the whole UI
            $exists = $storage->exists($tenant->logo_path);
            abort_unless($exists, 404);

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
    Log::error('Branding logo fetch failed: ' . $e->getMessage(), [
        'tenant_id' => $tenant->id,
        'disk' => $disk,
        'path' => $tenant->logo_path,
        'class' => get_class($e),
        'code' => $e->getCode(),
    ]);

    abort(404);
}
    }
}
