@extends('layouts.frontend.main')

@section('title', 'Sign In')

@section('content')
    <div class="container-fluid p-0">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4">

                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center justify-content-center mb-3">
                        <img src="{{ asset('asset/img/Raster_1024x1024_Transparent.png') }}"
                            alt="{{ config('app.name', 'NeptuneWare CRM') }}" style="height:42px;width:auto">
                    </a>

                    <h1 class="h2 mb-1">Welcome back</h1>
                    <p class="lead text-muted mb-0">Sign in to <strong>NeptuneWare CRM</strong> to continue.</p>
                </div>

                <div class="card mt-3 shadow-sm border-0">
                    <div class="card-body">
                        <div class="m-sm-3">

                            {{-- Flash status (e.g. reset link sent) --}}
                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('status') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

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

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input class="form-control form-control-lg @error('email') is-invalid @enderror"
                                        type="email" name="email" value="{{ old('email') }}"
                                        placeholder="name@company.com" required autofocus autocomplete="username">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label class="form-label mb-0">Password</label>

                                        @if (Route::has('password.request'))
                                            <a class="small" href="{{ route('password.request') }}">Forgot password?</a>
                                        @endif
                                    </div>

                                    <div class="input-group mt-2">
                                        <input id="login_password"
                                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                                            type="password" name="password" placeholder="••••••••" required
                                            autocomplete="current-password">
                                        <button class="btn btn-outline-secondary" type="button"
                                            data-toggle-password="#login_password">
                                            Show
                                        </button>
                                    </div>

                                    @error('password')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember_me"
                                            @checked(old('remember'))>
                                        <span class="form-check-label">Remember me</span>
                                    </label>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-lg btn-primary">
                                        Sign in
                                    </button>

                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="btn btn-lg btn-outline-secondary">
                                            Create account
                                        </a>
                                    @endif
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
