@extends('layouts.frontend.main')

@section('title', 'Forgot Password')

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

                    <h1 class="h2 mb-1">Forgot your password?</h1>
                    <p class="lead text-muted mb-0">
                        Enter your email and we’ll send you a reset link.
                    </p>
                </div>

                <div class="card mt-3 shadow-sm border-0">
                    <div class="card-body">
                        <div class="m-sm-3">

                            {{-- Session status (reset link sent) --}}
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

                            <div class="text-muted small mb-3">
                                We’ll email you a secure password reset link. If you don’t see it, check spam/junk.
                            </div>

                            <form method="POST" action="{{ route('password.email') }}">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input id="email"
                                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                                        type="email" name="email" value="{{ old('email') }}"
                                        placeholder="name@company.com" required autofocus autocomplete="username">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-lg btn-primary">
                                        Email Password Reset Link
                                    </button>

                                    <a href="{{ route('login') }}" class="btn btn-lg btn-outline-secondary">
                                        Back to Sign in
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
