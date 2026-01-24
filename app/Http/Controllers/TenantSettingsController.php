<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TenantSettingsController extends Controller
{
    public function edit(Tenant $tenant)
    {
        // $tenant is route-bound by {tenant:subdomain}
        return view('tenant.settings.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'subdomain' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', // letters/numbers with hyphens
                Rule::unique('tenants', 'subdomain')->ignore($tenant->id),
            ],
            'logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'remove_logo' => ['nullable','boolean'],

            // ✅ NEW
            'bank_details' => ['nullable','string'],
        ]);

        $oldSubdomain = $tenant->subdomain;

        // Handle logo removal
        if ($request->boolean('remove_logo')) {
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $tenant->logo_path = null;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // delete old
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }

            $path = $request->file('logo')->store("tenants/{$tenant->id}/branding", 'public');
            $tenant->logo_path = $path;
        }

        // Update fields
        $tenant->name = $data['name'];
        $tenant->subdomain = $data['subdomain'];

        // ✅ NEW: bank details
        $tenant->bank_details = $data['bank_details'] ?? null;

        $tenant->save();

        // If subdomain changed, redirect to the new tenant URL
        if ($oldSubdomain !== $tenant->subdomain) {
            return redirect()
                ->route('tenant.settings.edit', ['tenant' => $tenant])
                ->with('success', 'Workspace updated. Subdomain changed, you are now on the new URL.');
        }

        return back()->with('success', 'Workspace updated.');
    }

}