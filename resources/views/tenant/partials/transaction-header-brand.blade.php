@props([
    'tenant',
    // Optional: override size if you want
    'logoHeight' => 56,
    'logoMaxWidth' => 180,
    // Optional: show address/meta
    'showAddress' => true,
    'showMeta' => true,
])

@php
    $hasLogo = !empty($tenant?->logo_path);

    // Serve via controller route (works even if bucket is private)
    $logoSrc = $hasLogo
        ? tenant_route('tenant.branding.logo', ['tenant' => $tenant->subdomain ?? $tenant]) .
            '?v=' .
            optional($tenant->updated_at)->timestamp
        : null;

    $initial = strtoupper(substr((string) ($tenant->name ?? 'T'), 0, 1));

    $meta = collect([
        !empty($tenant->vat_number) ? 'VAT: ' . $tenant->vat_number : null,
        !empty($tenant->registration_number) ? 'Reg: ' . $tenant->registration_number : null,
    ])
        ->filter()
        ->implode(' • ');
@endphp

<div class="d-flex align-items-center gap-3">
    @if ($hasLogo)
        <img src="{{ $logoSrc }}" alt="Logo"
            style="height:{{ (int) $logoHeight }}px; width:auto; max-width:{{ (int) $logoMaxWidth }}px;"
            class="border rounded p-1 bg-white"
            onerror="this.style.display='none'; this.closest('.d-flex')?.querySelector('.logo-fallback')?.classList.remove('d-none');">

        <div class="logo-fallback d-none rounded bg-light border d-flex align-items-center justify-content-center"
            style="height:{{ (int) $logoHeight }}px; width:{{ (int) $logoHeight }}px;">
            <span class="text-muted fw-semibold">{{ $initial }}</span>
        </div>
    @else
        <div class="rounded bg-light border d-flex align-items-center justify-content-center"
            style="height:{{ (int) $logoHeight }}px; width:{{ (int) $logoHeight }}px;">
            <span class="text-muted fw-semibold">{{ $initial }}</span>
        </div>
    @endif

    <div>
        <div class="fw-semibold" style="font-size: 18px;">{{ $tenant->name }}</div>

        @if ($showAddress && !empty($tenant->company_address))
            <div class="text-muted small" style="white-space: pre-line; line-height: 1.25;">
                {{ $tenant->company_address }}
            </div>
        @endif

        @if ($showMeta && !empty($meta))
            <div class="text-muted small mt-1">
                <span>{{ $meta }}</span>
            </div>
        @endif
    </div>
</div>
