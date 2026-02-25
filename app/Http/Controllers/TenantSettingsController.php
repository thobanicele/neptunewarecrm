<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Subscription;

class TenantSettingsController extends Controller
{
    protected function tenantLogoDisk(): string
    {
        // If you added filesystems.tenant_logo_disk config, use it; else default to tenant_logos
        return (string) config('filesystems.tenant_logo_disk', 'tenant_logos');
    }

    public function index(Tenant $tenant)
    {
        return view('tenant.settings.index', compact('tenant'));
    }

    public function edit(string $tenantKey)
    {
        $tenant = app('tenant');

        $sub = Subscription::where('tenant_id', $tenant->id)->latest()->first();
        $trialDaysLeft = $sub?->trial_ends_at ? max(0, now()->diffInDays($sub->trial_ends_at, false)) : null;

        return view('tenant.settings.profile.index', compact('tenant', 'sub', 'trialDaysLeft'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        // ✅ Always trust resolved tenant context (prevents cross-tenant edits)
        $tenant = app('tenant');

        $section = $request->input('_section', 'profile');

        if ($section === 'profile') {
            $data = $request->validate([
                'company_address' => ['nullable', 'string', 'max:2000'],
                'vat_number' => ['nullable', 'string', 'max:64'],
                'registration_number' => ['nullable', 'string', 'max:64'],
                'bank_details' => ['nullable', 'string', 'max:5000'],
            ]);

            $tenant->forceFill([
                'company_address' => $data['company_address'] ?? null,
                'vat_number' => $data['vat_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'bank_details' => $data['bank_details'] ?? null,
            ])->save();

            return back()->with('success', 'Profile updated.');
        }

        return back()->with('error', 'Invalid update section.');
    }

    public function brandingEdit(string $tenantKey)
    {
        $tenant = app('tenant');

        $sub = Subscription::where('tenant_id', $tenant->id)->latest()->first();
        $trialDaysLeft = $sub?->trial_ends_at ? max(0, now()->diffInDays($sub->trial_ends_at, false)) : null;

        return view('tenant.settings.branding.index', compact('tenant', 'sub', 'trialDaysLeft'));
    }

    public function brandingUpdate(Request $request, Tenant $tenant)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'subdomain')->ignore($tenant->id),
            ],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $oldSubdomain = $tenant->subdomain;
        $disk = $this->tenantLogoDisk();

        // Remove logo
        if ($request->boolean('remove_logo')) {
            if ($tenant->logo_path) {
                Storage::disk($disk)->delete($tenant->logo_path);
            }
            $tenant->logo_path = null;
        }

        // Upload logo (stable filename)
        // Upload logo (stable filename)
        if ($request->hasFile('logo')) {
            if ($tenant->logo_path) {
                Storage::disk($disk)->delete($tenant->logo_path);
            }

            $file = $request->file('logo');
            $ext  = strtolower($file->getClientOriginalExtension() ?: 'png');

            $path = "tenants/{$tenant->id}/branding/logo.{$ext}";

            // Put with explicit public visibility (works better across S3/R2)
            Storage::disk($disk)->putFileAs(
                "tenants/{$tenant->id}/branding",
                $file,
                "logo.{$ext}",
                ['visibility' => 'public']
            );

            $tenant->logo_path = $path;
        }

        // Update fields
        $tenant->name = $data['name'];
        $tenant->subdomain = $data['subdomain'];
        $tenant->save();

        // Redirect if subdomain changed
        if ($oldSubdomain !== $tenant->subdomain) {
            return redirect()
                ->route('tenant.settings.branding', ['tenant' => $tenant->subdomain])
                ->with('success', 'Branding updated. Subdomain changed, you are now on the new URL.');
        }

        return back()->with('success', 'Branding updated.');
    }
}