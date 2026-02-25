@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="mb-1">
                    <a class="text-decoration-none small text-muted"
                        href="{{ tenant_route('tenant.settings.index', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                        <i class="fa-solid fa-gear me-1"></i> Settings
                    </a>
                    <span class="text-muted small mx-2">/</span>
                    <span class="text-muted small">Branding</span>
                </div>
                <h1 class="h3 mb-0">Branding</h1>
                <div class="text-muted small">Workspace name, URL and logo.</div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary"
                    href="{{ tenant_route('tenant.settings.edit', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                    <i class="fa-solid fa-building me-2"></i> Organization Profile
                </a>

                <a class="btn btn-outline-secondary"
                    href="{{ tenant_route('tenant.settings.index', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                    Back to Settings
                </a>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-success">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            @push('scripts')
                <script>
                    setTimeout(() => {
                        const el = document.getElementById('flash-success');
                        if (!el) return;
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    }, 3500);
                </script>
            @endpush
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-semibold mb-2">Please fix the following:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Quick links --}}
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <div class="fw-semibold">Quick links</div>
                    <div class="text-muted small">Common settings actions.</div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ tenant_route('tenant.tax-types.index') }}"
                        class="btn btn-outline-primary {{ request()->routeIs('tenant.tax-types.*') ? 'active' : '' }}">
                        <i class="fe fe-percent me-2"></i> Tax Types (VAT)
                    </a>
                </div>
            </div>
        </div>
        @php
            $logoDisk = config('filesystems.tenant_logo_disk', 'tenant_logos');
            $logoUrl = null;

            if (!empty($tenant->logo_path)) {
                try {
                    // Try public URL first
                    $logoUrl = Storage::disk($logoDisk)->url($tenant->logo_path);

                    // Optional: if your disk returns relative URLs sometimes, make absolute
                    // $logoUrl = \Illuminate\Support\Str::startsWith($logoUrl, ['http://','https://'])
                    //     ? $logoUrl
                    //     : url($logoUrl);
                } catch (\Throwable $e) {
                    $logoUrl = null;
                }

                // Fallback: signed URL (works when bucket is private)
                if (!$logoUrl) {
                    try {
                        $logoUrl = Storage::disk($logoDisk)->temporaryUrl($tenant->logo_path, now()->addMinutes(30));
                    } catch (\Throwable $e) {
                        $logoUrl = null;
                    }
                }
            }
        @endphp

        {{-- Branding form --}}
        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <div class="fw-semibold">Workspace branding</div>
                        <div class="text-muted small">
                            Name + URL affects your workspace identity. Logo appears in PDFs & UI (where enabled).
                        </div>
                    </div>

                    <div class="text-muted small">
                        Storage: <span class="badge bg-light text-dark border">{{ $logoDisk }}</span>

                    </div>
                </div>

                <form method="POST"
                    action="{{ tenant_route('tenant.settings.branding.update', ['tenant' => $tenant->subdomain ?? $tenant]) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Workspace name</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $tenant->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Workspace URL</label>
                            <div class="input-group">
                                <span class="input-group-text">/t/</span>
                                <input type="text" name="subdomain" class="form-control"
                                    value="{{ old('subdomain', $tenant->subdomain) }}" required>
                            </div>
                            <div class="form-text">
                                Lowercase letters/numbers + hyphens only. Changing this changes your workspace URL.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <div class="form-text">Recommended: transparent PNG/WebP, square-ish, 512×512.</div>

                            @if ($tenant->logo_path)
                                <div class="mt-2 d-flex align-items-center gap-3">
                                    @if ($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="Logo"
                                            style="height:56px; width:auto; max-width:180px;"
                                            class="border rounded p-1 bg-white">
                                    @else
                                        <div class="text-muted small">Logo is set but URL could not be generated.</div>
                                    @endif

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" name="remove_logo"
                                            id="remove_logo">
                                        <label class="form-check-label" for="remove_logo">Remove logo</label>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Placeholder (future) --}}
                        <div class="col-md-6">
                            <label class="form-label">PDF Branding (coming soon)</label>
                            <div class="border rounded p-3 text-muted small">
                                Next: watermark, footer text, accent color, and template options.
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary">Save Branding</button>
                    </div>

                </form>
            </div>
        </div>

    </div>
@endsection
