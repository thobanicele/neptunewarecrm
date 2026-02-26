@props([
    'tenant',
    'logoHeight' => 60,
])

@php
    // DomPDF prefers local filesystem images
    $logoPath = $tenant->logo_path ? public_path('storage/' . $tenant->logo_path) : null;
    $hasLogo = $logoPath && file_exists($logoPath);

    $tenantAddress = trim((string) ($tenant->address ?? $tenant->company_address ?? ''));
    $tenantVatNo   = trim((string) ($tenant->vat_number ?? ''));

    $meta = collect([
        !empty($tenant->vat_number) ? 'VAT: ' . $tenant->vat_number : null,
        !empty($tenant->registration_number) ? 'Reg: ' . $tenant->registration_number : null,
    ])->filter()->implode(' • ');
@endphp

@if ($hasLogo)
    <div>
        <img src="{{ $logoPath }}" alt="Logo" style="height:{{ (int) $logoHeight }}px; width:auto;">
    </div>
@endif

<div style="margin-top:6px; font-weight:700; font-size:14px;">
    {{ $tenant->name }}
</div>

@if ($tenantAddress)
    <div class="small muted pre mt-6">{{ $tenantAddress }}</div>
@endif

@if ($meta)
    <div class="small muted mt-6">{{ $meta }}</div>
@endif

@if ($tenantVatNo && !$meta)
    <div class="small muted mt-6">VAT Number: {{ $tenantVatNo }}</div>
@endif