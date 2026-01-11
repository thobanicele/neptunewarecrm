@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 720px;">
    <h1 class="h3 mb-2">Create your workspace</h1>
    <p class="text-muted mb-4">
        Your workspace will be available at:
        <strong><span id="previewSub">yourname</span>.crm.test</strong>
    </p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('tenant.onboarding.store') }}" class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label">Workspace name</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control"
                        placeholder="e.g. NeptuneWare CRM"
                        required
                    />
                </div>

                <div class="col-12">
                    <label class="form-label">Subdomain</label>
                    <div class="input-group">
                        <input
                            type="text"
                            name="subdomain"
                            id="subdomain"
                            value="{{ old('subdomain') }}"
                            class="form-control @error('subdomain') is-invalid @enderror"
                            placeholder="neptuneware"
                            required
                        />
                        <span class="input-group-text">.crm.test</span>
                        @error('subdomain')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">Letters, numbers, hyphens. No spaces.</div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Create workspace
                    </button>

                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('subdomain');
    const preview = document.getElementById('previewSub');
    const normalize = (v) => (v || 'yourname')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9-]/g, '')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');

    const update = () => preview.textContent = normalize(input.value);

    input.addEventListener('input', update);
    update();
})();
</script>
@endsection

