<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class TenantBrandingLogoController extends Controller
{
    public function show(string $tenant)
    {
        $tenantModel = app('tenant'); // your middleware sets this

        abort_unless($tenantModel && $tenantModel->subdomain === $tenant, 404);
        abort_unless($tenantModel->logo_path, 404);

        $disk = config('filesystems.tenant_logo_disk', 'tenant_logos');

        abort_unless(Storage::disk($disk)->exists($tenantModel->logo_path), 404);

        $stream = Storage::disk($disk)->readStream($tenantModel->logo_path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => Storage::disk($disk)->mimeType($tenantModel->logo_path) ?: 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
