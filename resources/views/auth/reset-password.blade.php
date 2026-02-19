@extends('layouts.frontend.main')

@section('title', 'Reset Password')

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

                    <h1 class="h2 mb-1">Reset your password</h1>
                    <p class="lead text-muted mb-0">Choose a new password to regain access.</p>
                </div>

                <div class="card mt-3 shadow-sm border-0">
                    <div class="card-body">
                        <div class="m-sm-3">

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

                            <form method="POST" action="{{ route('password.store') }}">
                                @csrf

                                {{-- Password Reset Token --}}
                                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                {{-- Email --}}
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input id="email"
                                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                                        type="email" name="email" value="{{ old('email', $request->email) }}" required
                                        autofocus autocomplete="username" readonly>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        This is the email address the reset link was sent to.
                                    </div>
                                </div>

                                {{-- Password --}}
                                <div class="mb-3">
                                    <label class="form-label">New password</label>
                                    <div class="input-group mt-2">
                                        <input id="reset_password"
                                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                                            type="password" name="password" placeholder="Create a new password" required
                                            autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button"
                                            data-toggle-password="#reset_password">
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
                                    <label class="form-label">Confirm new password</label>
                                    <div class="input-group mt-2">
                                        <input id="reset_password_confirmation"
                                            class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror"
                                            type="password" name="password_confirmation"
                                            placeholder="Confirm your new password" required autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button"
                                            data-toggle-password="#reset_password_confirmation">
                                            Show
                                        </button>
                                    </div>

                                    @error('password_confirmation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-lg btn-primary">
                                        Reset Password
                                    </button>

                                    <a href="{{ route('login') }}" class="btn btn-lg btn-outline-secondary">
                                        Back to Sign in
                                    </a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                {{-- Brand footer --}}
                <div class="text-center mb-3 text-muted small">
                    <span class="fw-semibold">{{ config('app.name', 'NeptuneWare CRM') }}</span>
                    — a product of <span class="fw-semibold">NeptuneWare (Pty) Ltd</span>
                    • © {{ now()->year }}
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
