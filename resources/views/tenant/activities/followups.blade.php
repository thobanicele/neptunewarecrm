@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="mb-0">Activity List</h3>
                <div class="text-muted small">
                    Due activities (overdue first) • Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})
                </div>

                <div class="d-flex gap-2 mt-2 flex-wrap">
                    <span class="badge text-bg-danger">Overdue: {{ $overdueCount }}</span>
                    <span class="badge text-bg-warning text-dark">Open: {{ $openCount }}</span>
                    <span class="badge text-bg-success">Done: {{ $doneCount }}</span>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.deals.index') }}" class="btn btn-outline-secondary">Deals</a>
                <a href="{{ tenant_route('tenant.leads.index') }}" class="btn btn-outline-secondary">Leads</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Search</label>
                        <input class="form-control" name="q" value="{{ $q }}"
                            placeholder="Title or notes...">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="open" @selected($status === 'open')>Open</option>
                            <option value="done" @selected($status === 'done')>Done</option>
                            <option value="all" @selected($status === 'all')>All</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Scope</label>
                        <select class="form-select" name="scope">
                            <option value="" @selected(!$scope)>All</option>
                            <option value="deal" @selected($scope === 'deal')>Deals</option>
                            <option value="contact" @selected($scope === 'contact')>Leads</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="" @selected(!$type)>All</option>
                            <option value="call" @selected($type === 'call')>Call</option>
                            <option value="meeting" @selected($type === 'meeting')>Meeting</option>
                            <option value="email" @selected($type === 'email')>Email</option>
                            <option value="note" @selected($type === 'note')>Note</option>
                        </select>
                    </div>

                    {{-- ✅ Right side: Show all + buttons in one md column --}}
                    <div class="col-12 col-md-2">
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="show_all" value="1"
                                    id="showAll" @checked($showAll)>
                                <label class="form-check-label" for="showAll">Show all</label>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary w-100" type="submit">Filter</button>
                                <a class="btn btn-light w-100"
                                    href="{{ tenant_route('tenant.activities.followups') }}">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Title / Notes</th>
                            <th>Due</th>
                            <th>Owner</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($items as $a)
                            @php
                                $isDone = !is_null($a->done_at);
                                $isOverdue = !$isDone && $a->due_at && $a->due_at->isPast();

                                $typePill = match ($a->type) {
                                    'call' => 'primary',
                                    'meeting' => 'info',
                                    'email' => 'warning',
                                    'note' => 'secondary',
                                    default => 'dark',
                                };

                                $subject = $a->subject;
                                $subjectLabel = $subject ? class_basename($subject) : '—';

                                $subjectTitle = '—';
                                $subjectUrl = null;

                                if ($subject instanceof \App\Models\Deal) {
                                    $subjectTitle = $subject->title;
                                    $subjectUrl = tenant_route('tenant.deals.show', ['deal' => $subject->id]);
                                    $subjectLabel = 'Deal';
                                } elseif ($subject instanceof \App\Models\Contact) {
                                    $subjectTitle = $subject->name;
                                    $subjectUrl = tenant_route('tenant.leads.edit', ['contact' => $subject->id]);
                                    $subjectLabel = 'Lead';
                                }
                            @endphp

                            <tr>
                                <td>
                                    <span
                                        class="badge rounded-pill text-bg-{{ $typePill }}">{{ strtoupper($a->type) }}</span>
                                    @if ($isDone)
                                        <span class="badge rounded-pill text-bg-success ms-1">DONE</span>
                                    @elseif($isOverdue)
                                        <span class="badge rounded-pill text-bg-danger ms-1">OVERDUE</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $subjectLabel }}</div>
                                    @if ($subjectUrl)
                                        <a href="{{ $subjectUrl }}" class="small">{{ $subjectTitle }}</a>
                                    @else
                                        <span class="text-muted small">Missing link</span>
                                    @endif
                                </td>

                                <td style="max-width: 420px;">
                                    <div class="fw-semibold">{{ $a->title ?? 'Activity' }}</div>
                                    @if ($a->body)
                                        <div class="text-muted small text-truncate" style="max-width: 420px;">
                                            {{ $a->body }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    @if ($a->due_at)
                                        <div class="{{ $isOverdue ? 'text-danger fw-semibold' : '' }}">
                                            {{ $a->due_at->format('Y-m-d H:i') }}
                                        </div>
                                        <div class="text-muted small">{{ $a->due_at->diffForHumans() }}</div>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>{{ $a->user?->name ?? '—' }}</td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <form method="POST"
                                            action="{{ tenant_route('tenant.activities.toggle', ['activity' => $a->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">
                                                {{ $isDone ? 'Undo' : 'Done' }}
                                            </button>
                                        </form>

                                        <form method="POST"
                                            action="{{ tenant_route('tenant.activities.destroy', ['activity' => $a->id]) }}"
                                            onsubmit="return confirm('Delete this activity?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    No follow-ups found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $items->links() }}
            </div>
        </div>

    </div>
@endsection
