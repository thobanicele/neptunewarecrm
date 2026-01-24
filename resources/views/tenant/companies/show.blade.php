@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">
    <h1 class="h3 mb-3">{{ $company->name }}</h1>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div><strong>Type:</strong> {{ $company->type }}</div>
                    <div><strong>Email:</strong> {{ $company->email ?? '—' }}</div>
                    <div><strong>Phone:</strong> {{ $company->phone ?? '—' }}</div>
                    <div><strong>Industry:</strong> {{ $company->industry ?? '—' }}</div>
                    <div class="mt-2"><strong>Address:</strong><br>{{ $company->address ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><strong>Contacts</strong></div>
                <div class="card-body">
                    @forelse($company->contacts as $c)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ $c->name }}</div>
                            <div class="small text-muted">{{ $c->email ?? '—' }} • {{ $c->phone ?? '—' }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No contacts yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
