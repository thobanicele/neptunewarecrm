@extends('layouts.frontend.onboardinglayout')

@section('content')
    <div class="container-fluid p-0">

        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-7 col-xl-6">

                <div class="mb-4">
                    <h1 class="h3 mb-1">Create your workspace</h1>
                    <p class="text-muted mb-0">
                        Your workspace URL will look like:
                        <span class="fw-semibold">{{ url('/t') }}/</span>
                        <span class="fw-semibold text-dark">&lt;tenant&gt;</span>
                        <span class="fw-semibold">/dashboard</span>
                    </p>
                </div>

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
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" action="{{ route('tenant.onboarding.store') }}" class="needs-validation"
                            novalidate id="onboardingForm">
                            @csrf

                            {{-- default action --}}
                            <input type="hidden" name="go" id="goField" value="{{ old('go', 'dashboard') }}">
                            <input type="hidden" name="trial" id="trialField" value="{{ old('trial', '0') }}">

                            <div class="mb-3">
                                <label class="form-label">Workspace name</label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="e.g. ALTSA International" required>
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

                                    <input type="text" name="subdomain" value="{{ old('subdomain') }}"
                                        class="form-control @error('subdomain') is-invalid @enderror" placeholder="acme"
                                        pattern="[A-Za-z0-9-]+" required>

                                    <span class="input-group-text">/dashboard</span>

                                    @error('subdomain')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Use letters, numbers, and hyphens only.</div>
                                    @enderror
                                </div>

                                <div class="form-text">Letters, numbers, hyphens. No spaces.</div>
                            </div>

                            {{-- Trial options (only matters when starting trial) --}}
                            <div class="border rounded p-3 bg-light mb-3" id="trialBox">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-semibold">Premium Trial</div>
                                    <span class="badge bg-success-subtle text-success">14 days</span>
                                </div>

                                <div class="text-muted small mt-1">
                                    Choose your billing cycle now. You’ll be redirected to upgrade to activate the trial.
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label mb-1">Billing cycle</label>
                                        <select class="form-select" name="cycle" id="cycleField">
                                            <option value="monthly" @selected(old('cycle', 'monthly') === 'monthly')>Monthly</option>
                                            <option value="yearly" @selected(old('cycle') === 'yearly')>Yearly</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-end gap-2 mt-4 flex-wrap">
                                <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                                    Cancel
                                </a>

                                {{-- Normal create: explicitly set go=dashboard --}}
                                <button type="submit" class="btn btn-primary"
                                    onclick="document.getElementById('goField').value='dashboard'; document.getElementById('trialField').value='0';">
                                    Create workspace
                                </button>

                                {{-- Create + start trial: sets go=upgrade + trial=1 --}}
                                <button type="submit" class="btn btn-dark"
                                    onclick="document.getElementById('goField').value='upgrade'; document.getElementById('trialField').value='1';">
                                    Create &amp; start trial
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        Tip: keep it short (e.g. <span class="fw-semibold">altsa</span>, <span
                            class="fw-semibold">neptuneware</span>).
                    </small>
                </div>

            </div>
        </div>

    </div>

    <script>
        // Bootstrap validation helper
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
