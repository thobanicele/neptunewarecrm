@extends('layouts.app')

@section('content')
    <div class="max-w-2xl mx-auto p-6">
        @if (session('error'))
            <div class="alert alert-warning">{{ session('error') }}</div>
        @endif

        <h1 class="text-2xl font-semibold mb-2">Upgrade Plan</h1>
        <p class="text-gray-600 mb-4">
            Billing is coming next. For now, this is a placeholder.
        </p>

        <div class="p-4 rounded border bg-gray-50">
            <p><b>Current plan:</b> {{ $tenant->plan }}</p>
            <p class="mt-2 text-sm text-gray-600">
                Free plan limits deals to {{ config('tenant_limits.free.max_deals') }}.
            </p>
        </div>

        <div class="mt-6">
            <a class="underline" href="{{ tenant_route('tenant.dashboard', ['tenant' => $tenant->subdomain]) }}">Back to
                dashboard</a>
        </div>
    </div>
@endsection
