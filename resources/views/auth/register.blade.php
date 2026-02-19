@extends('layouts.frontend.main')

@section('title', 'Sign Up')

@section('content')
    <div class="container-fluid p-0">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4">

                {{-- Brand header --}}
                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center justify-content-center mb-3">
                        <img src="{{ asset('asset/img/Raster_1024x1024_Transparent.png') }}"
                            alt="{{ config('app.name', 'NeptuneWare CRM') }}" style="height:42px;width:auto">
                    </a>

                    <h1 class="h2 mb-1">Create your account</h1>
                    <p class="lead text-muted mb-0">Start your workspace in minutes.</p>
                </div>

                <div class="card mt-3 shadow-sm border-0">
                    <div class="card-body">
                        <div class="m-sm-3">

                            {{-- Flash error (custom) --}}
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            {{-- Validation errors --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <div class="fw-bold mb-1">Please fix the following:</div>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $e)
                                            <li>{{ $e }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('register') }}">
                                @csrf

                                {{-- Name --}}
                                <div class="mb-3">
                                    <label class="form-label">Full name</label>
                                    <input id="name"
                                        class="form-control form-control-lg @error('name') is-invalid @enderror"
                                        type="text" name="name" value="{{ old('name') }}"
                                        placeholder="Enter your full name" required autofocus autocomplete="name" />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Email --}}
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input id="email"
                                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                                        type="email" name="email" value="{{ old('email') }}"
                                        placeholder="name@company.com" required autocomplete="username" />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Password --}}
                                <div class="mb-3">
                                    <label class="form-label">Password</label>

                                    <div class="input-group mt-2">
                                        <input id="register_password"
                                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                                            type="password" name="password" placeholder="Create a password" required
                                            autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button"
                                            data-toggle-password="#register_password">
                                            Show
                                        </button>
                                    </div>

                                    @error('password')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror

                                    <div class="form-text">
                                        Use at least 8 characters with a mix of letters and numbers.
                                    </div>
                                </div>

                                {{-- Confirm Password --}}
                                <div class="mb-3">
                                    <label class="form-label">Confirm password</label>

                                    <div class="input-group mt-2">
                                        <input id="register_password_confirmation"
                                            class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror"
                                            type="password" name="password_confirmation" placeholder="Confirm your password"
                                            required autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button"
                                            data-toggle-password="#register_password_confirmation">
                                            Show
                                        </button>
                                    </div>

                                    @error('password_confirmation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- SaaS notice --}}
                                <div class="text-muted small mb-3">
                                    By creating an account, you agree to our
                                    <a href="https://crm.neptuneware.com/terms-of-service" target="_blank"
                                        rel="noopener">Terms</a>
                                    and
                                    <a href="https://crm.neptuneware.com/privacy-policy" target="_blank"
                                        rel="noopener">Privacy Policy</a>.
                                </div>

                                <div class="d-grid gap-2 mt-3">
                                    <button type="submit" class="btn btn-lg btn-primary">
                                        Create account
                                    </button>

                                    <a href="{{ route('login') }}" class="btn btn-lg btn-outline-secondary">
                                        I already have an account
                                    </a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-toggle-password]');
            if (!btn) return;

            const selector = btn.getAttribute('data-toggle-password');
            const input = document.querySelector(selector);
            if (!input) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.textContent = isHidden ? 'Hide' : 'Show';
        });
    </script>
@endpush
