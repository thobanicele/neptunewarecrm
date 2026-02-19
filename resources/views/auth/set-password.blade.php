@extends('layouts.frontend.main')

@section('content')
    <div class="container py-5" style="max-width:520px;">
        <h1 class="h4 mb-2">Set your password</h1>
        <p class="text-muted mb-4">Create a password to access your workspace.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.setup.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">New password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Save password</button>
        </form>
    </div>
@endsection
