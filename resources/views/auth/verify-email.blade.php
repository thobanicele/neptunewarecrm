@extends('layouts.app')

@section('content')
    <div class="container py-4" style="max-width: 720px;">
        <h3 class="mb-2">Verify your email</h3>
        <p class="text-muted">
            We’ve sent a verification link to your email address. Please verify to continue.
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success">
                A new verification link has been sent to your email address.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-primary">Resend verification email</button>
        </form>

        <hr class="my-4">

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-secondary">Logout</button>
        </form>
    </div>
@endsection
