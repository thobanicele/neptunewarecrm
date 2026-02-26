@props([
    'tenant',
    'logoHeight' => 60,
    // pass this from controller (local temp file path)
    'pdfLogoPath' => null,
])

@php
    // DomPDF prefers local filesystem images.
    // Use controller-provided temp path first, fallback to public/storage (for local dev setups).
    $logoPath = null;

    if (!empty($pdfLogoPath) && is_string($pdfLogoPath) && file_exists($pdfLogoPath)) {
        $logoPath = $pdfLogoPath;
    } else {
        $fallback = $tenant->logo_path ? public_path('storage/' . ltrim($tenant->logo_path, '/')) : null;
        if ($fallback && file_exists($fallback)) {
            $logoPath = $fallback;
        }
    }

    $hasLogo = !empty($logoPath);

    $tenantAddress = trim((string) ($tenant->address ?? ($tenant->company_address ?? '')));
    $tenantVatNo = trim((string) ($tenant->vat_number ?? ''));

    $meta = collect([
        !empty($tenant->vat_number) ? 'VAT: ' . $tenant->vat_number : null,
        !empty($tenant->registration_number) ? 'Reg: ' . $tenant->registration_number : null,
    ])
        ->filter()
        ->implode(' • ');
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
@elseif ($tenantVatNo)
    <div class="small muted mt-6">VAT Number: {{ $tenantVatNo }}</div>
@endif
