@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">{{ $deal->title }}</h3>

                @php
                    $stageName = $deal->stage?->name ?? 'N/A';
                @endphp

                <div class="text-muted">
                    Stage: {{ strtoupper($stageName) }}
                    • Amount: R {{ number_format((float) $deal->amount, 2) }}
                    @if ($deal->expected_close_date)
                        • Close: {{ \Carbon\Carbon::parse($deal->expected_close_date)->format('d M Y') }}
                    @endif
                </div>

                <div class="text-muted small mt-1">
                    Company: {{ $deal->company?->name ?? '—' }}
                    • Contact: {{ $deal->primaryContact?->name ?? '—' }}
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.deals.index') }}" class="btn btn-outline-secondary">Back</a>
                <a href="{{ tenant_route('tenant.deals.edit', ['deal' => $deal->id]) }}"
                    class="btn btn-outline-primary">Edit</a>
                <a href="{{ tenant_route('tenant.quotes.create', ['deal_id' => $deal->id]) }}" class="btn btn-primary">
                    + Create Quote
                </a>


            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- ✅ Polymorphic Activities Timeline (Deals milestone A) --}}
        @include('tenant.activities._timeline', [
            'activities' => $activities, // from controller: $deal->activities
            'subject_type' => 'deal',
            'subject_id' => $deal->id,
        ])

        {{-- ✅ Deal Notes (stored on deals table) --}}
        <div class="card mt-3">
            <div class="card-header fw-semibold">Deal Notes</div>
            <div class="card-body">
                @if ($deal->notes)
                    <div class="text-muted" style="white-space: pre-wrap;">{{ $deal->notes }}</div>
                @else
                    <div class="text-muted">No deal notes yet.</div>
                @endif
            </div>
        </div>

    </div>
@endsection
