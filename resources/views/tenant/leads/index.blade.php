@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-0 d-flex align-items-center gap-2">
                    Leads
                    <span class="badge bg-light text-dark">
                        Total: {{ method_exists($leads, 'total') ? $leads->total() : $leads->count() }}
                    </span>
                </h1>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.leads.kanban') }}" class="btn btn-outline-secondary">Kanban view</a>
                <a href="{{ tenant_route('tenant.leads.create') }}" class="btn btn-primary">+ Add Lead</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form class="row g-2 align-items-end" method="GET" action="{{ tenant_route('tenant.leads.index') }}">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="q" value="{{ $q ?? '' }}"
                            placeholder="Name, email, phone...">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Stage</label>
                        <select class="form-select" name="stage">
                            <option value="">All</option>

                            @php
                                // supports either:
                                // 1) ['new' => 'New', 'contacted'=>'Contacted']
                                // 2) ['new','contacted']
                                $isAssoc = is_array($leadStages ?? null) && array_keys($leadStages) !== range(0, count($leadStages) - 1);
                            @endphp

                            @if ($isAssoc)
                                @foreach ($leadStages as $key => $label)
                                    <option value="{{ $key }}" @selected(($stage ?? '') === (string) $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            @else
                                @foreach (($leadStages ?? []) as $s)
                                    <option value="{{ $s }}" @selected(($stage ?? '') === (string) $s)>
                                        {{ ucwords(str_replace('_', ' ', $s)) }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                        <a class="btn btn-light w-100" href="{{ tenant_route('tenant.leads.index') }}">Reset</a>
                    </div>

                    {{-- ✅ Instant “filtered by” summary --}}
                    <div class="col-12">
                        <div class="text-muted small mt-2">
                            @php
                                $hasQ = !empty(trim((string)($q ?? '')));
                                $hasStage = !empty(trim((string)($stage ?? '')));
                            @endphp

                            @if (!$hasQ && !$hasStage)
                                Showing all leads.
                            @else
                                Filtered by:
                                @if ($hasQ)
                                    <span class="badge bg-light text-dark">Search: "{{ $q }}"</span>
                                @endif
                                @if ($hasStage)
                                    <span class="badge bg-light text-dark">Stage: {{ ucwords(str_replace('_',' ', $stage)) }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Email</th>
                                <th class="d-none d-md-table-cell">Phone</th>
                                <th>Stage</th>
                                <th class="text-end" style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leads as $lead)
                                @php
                                    $stageKey = $lead->lead_stage ?? 'new';
                                    $label = $stageKey;
                                    if (isset($leadStages) && is_array($leadStages)) {
                                        // if associative, use label
                                        $isAssoc = array_keys($leadStages) !== range(0, count($leadStages) - 1);
                                        if ($isAssoc && isset($leadStages[$stageKey])) {
                                            $label = $leadStages[$stageKey];
                                        }
                                    }
                                @endphp

                                <tr>
                                    <td class="fw-semibold">{{ $lead->name }}</td>
                                    <td class="d-none d-md-table-cell">{{ $lead->email ?? '—' }}</td>
                                    <td class="d-none d-md-table-cell">{{ $lead->phone ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ is_string($label) ? $label : ucwords(str_replace('_', ' ', $stageKey)) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary"
                                            href="{{ tenant_route('tenant.leads.edit', ['contact' => $lead->id]) }}">
                                            Edit
                                        </a>

                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.leads.kanban') }}">
                                            Kanban
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No leads found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (method_exists($leads, 'links'))
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            Showing {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }}
                            <span class="ms-2 badge bg-light text-dark">
                                Page {{ $leads->currentPage() }} / {{ $leads->lastPage() }}
                            </span>
                        </div>
                        <div>
                            {{ $leads->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>
@endsection


