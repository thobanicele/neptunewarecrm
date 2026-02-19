@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0" style="max-width:900px;">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-0">Contact Details</h1>
                <div class="text-muted small">{{ $contact->name }}</div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-light" href="{{ tenant_route('tenant.contacts.index') }}">Back</a>

                @can('update', $contact)
                    <a class="btn btn-outline-primary" href="{{ tenant_route('tenant.contacts.edit', $contact) }}">
                        Edit
                    </a>
                @endcan
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        @php
            $stage = trim((string) ($contact->lifecycle_stage ?? ''));
            $stageLabel = $stage ? ucwords(str_replace('_', ' ', $stage)) : '—';
        @endphp

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Name</div>
                        <div class="fw-semibold">{{ $contact->name }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Company</div>
                        <div class="fw-semibold">
                            @if ($contact->company)
                                <a class="text-decoration-none"
                                    href="{{ tenant_route('tenant.companies.show', $contact->company) }}">
                                    {{ $contact->company->name }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Email</div>
                        <div class="fw-semibold">{{ $contact->email ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Phone</div>
                        <div class="fw-semibold">{{ $contact->phone ?? '—' }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Lifecycle Stage</div>
                        <div class="fw-semibold">
                            <span class="badge bg-light text-dark">{{ $stageLabel }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Last Updated</div>
                        <div class="fw-semibold">{{ $contact->updated_at?->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>

                    <div class="col-12">
                        <div class="text-muted small">Notes</div>
                        <div class="fw-semibold">
                            {!! nl2br(e($contact->notes ?? '—')) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activities --}}
        @php
            $activities = $contact->relationLoaded('activities')
                ? $contact->activities
                : $contact->activities()->latest()->get();
        @endphp

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Activities</h5>
                </div>

                @if ($activities->isEmpty())
                    <div class="text-muted">No activities yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0 table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 180px;">Date</th>
                                    <th style="width: 140px;">Type</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activities as $a)
                                    @php
                                        $type = trim((string) ($a->type ?? 'activity'));
                                        $typeLabel = $type ? ucwords(str_replace('_', ' ', $type)) : 'Activity';
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $a->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                        <td>{{ $typeLabel }}</td>
                                        <td>{{ $a->note ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
