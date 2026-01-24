@extends('layouts.frontend.onboardinglayout')

@section('content')
<div class="container-fluid p-0">

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7 col-xl-6">

            <div class="mb-4">
                <h1 class="h3 mb-1">Create your workspace</h1>
                <p class="text-muted mb-0">
                    Your workspace URL will look like:
                    <span class="fw-semibold">{{ url('/t') }}/</span><span class="fw-semibold text-dark">&lt;tenant&gt;</span><span class="fw-semibold">/dashboard</span>
                </p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="fw-semibold mb-2">Please fix the following:</div>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ tenant_route('tenant.onboarding.store') }}" class="needs-validation" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Workspace name</label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="e.g. ALTSA International"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">This is the name your team will see.</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tenant key (URL)</label>

                            <div class="input-group">
                                <span class="input-group-text">{{ url('/t') }}/</span>

                                <input
                                    type="text"
                                    name="subdomain"
                                    value="{{ old('subdomain') }}"
                                    class="form-control @error('subdomain') is-invalid @enderror"
                                    placeholder="acme"
                                    pattern="[A-Za-z0-9-]+"
                                    required
                                >

                                <span class="input-group-text">/dashboard</span>

                                @error('subdomain')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">Use letters, numbers, and hyphens only.</div>
                                @enderror
                            </div>

                            <div class="form-text">Letters, numbers, hyphens. No spaces.</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 mt-4">
                            <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>

                            <button type="submit" class="btn btn-primary">
                                Create workspace
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    Tip: keep it short (e.g. <span class="fw-semibold">altsa</span>, <span class="fw-semibold">neptuneware</span>).
                </small>
            </div>

        </div>
    </div>

</div>

{{-- Optional: Bootstrap validation helper --}}
<script>
(() => {
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>
@endsection



