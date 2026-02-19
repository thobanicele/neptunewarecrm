@extends('layouts.app')

@section('content')
    <div class="container py-4">

        @php
            $stageKey = fn($s) => strtolower(trim((string) $s));

            // Only stage colors
            $stageColor = fn($name) => match ($stageKey($name)) {
                'new' => 'secondary',
                'qualified' => 'info',
                'proposal', 'proposal sent', 'proposal_sent' => 'warning',
                'negotiation' => 'primary',
                'won' => 'success',
                'lost' => 'danger',
                default => 'dark',
            };

            $stageStats = $stageStats ?? collect(); // keyed by stage_id (object with cnt)
            $allCount = $allCount ?? null;
        @endphp

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Deals</h3>
                <div class="text-muted">
                    Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.deals.kanban', ['tenant' => $tenant->subdomain]) }}"
                    class="btn btn-outline-secondary">
                    Kanban view
                </a>
                @can('create', \App\Models\Deal::class)
                    <a href="{{ tenant_route('tenant.deals.create', ['tenant' => $tenant->subdomain]) }}" class="btn btn-primary">
                        + Add Deal
                    </a>
                @endcan
            </div>
        </div>

        {{-- Stage filter pills (only color for stages) --}}
        <div class="mb-3 d-flex flex-wrap gap-2">
            <a class="badge rounded-pill text-bg-{{ $stageId ? 'light' : 'dark' }} px-3 py-2"
                href="{{ tenant_route('tenant.deals.index', ['tenant' => $tenant->subdomain]) }}">
                All
                @if (!is_null($allCount))
                    <span class="ms-1 opacity-75">({{ $allCount }})</span>
                @endif
            </a>

            @foreach ($stages as $stage)
                @php
                    $active = (string) $stageId === (string) $stage->id;
                    $color = $stageColor($stage->name);
                    $cnt = (int) ($stageStats[$stage->id]->cnt ?? 0);
                @endphp

                <a class="badge rounded-pill px-3 py-2 {{ $active ? "text-bg-{$color}" : 'text-bg-light' }}"
                    href="{{ tenant_route('tenant.deals.index', [
                        'tenant' => $tenant->subdomain,
                        'stage_id' => $stage->id,
                    ]) }}">
                    {{ strtoupper($stage->name) }}
                    <span class="ms-1 {{ $active ? 'opacity-75' : 'text-muted' }}">({{ $cnt }})</span>
                </a>
            @endforeach
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Stage</th>
                            <th class="text-end">Amount</th>
                            <th>Expected Close</th>
                            <th>Next follow-up</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($deals as $deal)
                            @php
                                $stageName = optional($deal->stage)->name ?? 'unknown';
                                $pill = $stageColor($stageName);
                                $next = $deal->next_followup_at ? \Carbon\Carbon::parse($deal->next_followup_at) : null;
                            @endphp

                            <tr>
                                {{-- Title column: ONLY title --}}
                                <td class="fw-semibold">{{ $deal->title }}</td>

                                <td>{{ $deal->company?->name ?? '—' }}</td>
                                <td>{{ $deal->primaryContact?->name ?? '—' }}</td>

                                <td>
                                    <span class="badge rounded-pill text-bg-{{ $pill }}">
                                        {{ strtoupper($stageName) }}
                                    </span>
                                </td>

                                <td class="text-end">R {{ number_format((float) $deal->amount, 2) }}</td>

                                <td>
                                    {{ $deal->expected_close_date ? \Carbon\Carbon::parse($deal->expected_close_date)->format('Y-m-d') : '-' }}
                                </td>
                                <td>
                                    @if (!$next)
                                        <span class="text-muted">—</span>
                                    @elseif($next->isPast())
                                        <span class="badge rounded-pill text-bg-danger">Overdue</span>
                                        <div class="text-muted small">{{ $next->diffForHumans() }}</div>
                                    @elseif($next->diffInHours(now()) <= 48)
                                        <span class="badge rounded-pill text-bg-warning text-dark">Due soon</span>
                                        <div class="text-muted small">{{ $next->diffForHumans() }}</div>
                                    @else
                                        <span class="badge rounded-pill text-bg-info">Scheduled</span>
                                        <div class="text-muted small">{{ $next->diffForHumans() }}</div>
                                    @endif
                                </td>

                                <td class="text-muted">{{ $deal->updated_at->diffForHumans() }}</td>

                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ tenant_route('tenant.deals.show', ['deal' => $deal->id]) }}">
                                        View
                                    </a>
                                    @can('update', $deal)
                                        <a class="btn btn-sm btn-outline-secondary"
                                            href="{{ tenant_route('tenant.deals.edit', ['deal' => $deal->id]) }}">
                                            Edit
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    No deals yet. Click <b>+ Add Deal</b> to create your first one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $deals->links() }}
            </div>
        </div>

    </div>
@endsection
