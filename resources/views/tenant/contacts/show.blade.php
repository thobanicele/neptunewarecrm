@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0" style="max-width:900px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Contact Details</h1>

            <div class="d-flex gap-2">
                <a class="btn btn-light" href="{{ tenant_route('tenant.contacts.index') }}">Back</a>

                <a class="btn btn-outline-primary" href="{{ tenant_route('tenant.contacts.edit', $contact) }}">
                    Edit
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Name</div>
                        <div class="fw-semibold">{{ $contact->name }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Company</div>
                        <div class="fw-semibold">{{ $contact->company?->name ?? '—' }}</div>
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
                        <div class="fw-semibold">{{ ucfirst($contact->lifecycle_stage) }}</div>
                    </div>

                    <div class="col-md-6">
                        <div class="text-muted small">Last Updated</div>
                        <div class="fw-semibold">{{ $contact->updated_at?->format('Y-m-d H:i') }}</div>
                    </div>

                    <div class="col-12">
                        <div class="text-muted small">Notes</div>
                        <div class="fw-semibold">{!! nl2br(e($contact->notes ?? '—')) !!}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activities --}}
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Activities</h5>

                @if ($contact->activities->isEmpty())
                    <div class="text-muted">No activities yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 180px;">Date</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($contact->activities as $a)
                                    <tr>
                                        <td>{{ $a->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ ucfirst($a->type ?? 'activity') }}</td>
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
