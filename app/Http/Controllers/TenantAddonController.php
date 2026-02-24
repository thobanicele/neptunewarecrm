<?php

namespace App\Http\Controllers;

use App\Models\TenantAddon;
use Illuminate\Http\Request;
use App\Support\TenantPlan;

class TenantAddonController extends Controller
{
    public function enable(Request $request, string $tenant)
    {
        $t = app('tenant');

        abort_unless(auth()->user()?->hasAnyRole(['super_admin','tenant_owner','tenant_admin']), 403);

        $data = $request->validate([
            'addon' => ['required','string','in:ecommerce'],
        ]);

        // ✅ internal-only gate (for now)
        if (config('ecommerce_internal.only', true)) {
            $raw = (string) config('ecommerce_internal.allowed', '');
            $allowed = array_values(array_filter(array_map('trim', explode(',', $raw))));

            $ok = in_array((string) $t->id, $allowed, true)
                || (!empty($t->subdomain) && in_array((string) $t->subdomain, $allowed, true));

            abort_unless($ok, 403);
        }

        $addon = $data['addon'];

        $row = TenantAddon::updateOrCreate(
            ['tenant_id' => $t->id, 'key' => $addon],
            [
                'is_enabled' => true,
                'enabled_at' => now(),
                'enabled_by_user_id' => auth()->id(),
            ]
        );

        if (class_exists(\App\Services\ActivityLogger::class)) {
            app(\App\Services\ActivityLogger::class)->log(
                $t->id,
                'tenant.addon_enabled',
                $row,
                ['addon' => $addon],
                auth()->id()
            );
        }

        return back()->with('success', 'Add-on enabled: ' . ucfirst($addon) . '.');
    }

    public function disable(Request $request, string $tenant)
    {
        $t = app('tenant');

        abort_unless(auth()->user()?->hasAnyRole(['super_admin','tenant_owner','tenant_admin']), 403);

        $data = $request->validate([
            'addon' => ['required','string','in:ecommerce'],
        ]);

        $addon = $data['addon'];

        $row = TenantAddon::where('tenant_id', $t->id)->where('key', $addon)->first();
        if ($row) {
            $row->is_enabled = false;
            $row->save();
        }

        if (class_exists(\App\Services\ActivityLogger::class)) {
            app(\App\Services\ActivityLogger::class)->log(
                $t->id,
                'tenant.addon_disabled',
                $row,
                ['addon' => $addon],
                auth()->id()
            );
        }

        return back()->with('success', 'Add-on disabled: ' . ucfirst($addon) . '.');
    }
}
