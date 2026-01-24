@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            // Normalize stage key (handles "New Lead" or "new_lead")
            $stageKey = fn($s) => strtolower(str_replace(' ', '_', trim($s)));

            $stageLabel = fn($s) => ucwords(str_replace('_', ' ', $stageKey($s)));

            // Deals-like color theme
            $stageColor = fn($s) => match ($stageKey($s)) {
                'new_lead' => 'secondary',
                'qualified' => 'info',
                'proposal_sent' => 'warning',
                'negotiation' => 'primary',
                'won' => 'success',
                'lost' => 'danger',
                default => 'dark',
            };

            // Total leads (for % + progress bars)
            $total = 0;
            foreach ($leadStages as $s) {
                $total += ($leads[$s] ?? collect())->count();
            }
        @endphp

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="h3 mb-0">Leads – Kanban</h1>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.leads.index') }}" class="btn btn-outline-secondary">List view</a>
                <a href="{{ tenant_route('tenant.leads.create') }}" class="btn btn-primary">+ Add Lead</a>
            </div>
        </div>

        <div class="text-muted small mb-2">
            Total leads: <span id="totalLeads">{{ $total }}</span>
        </div>

        {{-- Toast (Bootstrap / AdminKit) --}}
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
            <div id="appToast" class="toast align-items-center text-bg-dark border-0" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="appToastBody">Done.</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        </div>

        {{-- Deals-style Kanban UI --}}
        <style>
            .kanban-wrap {
                overflow-x: auto;
                padding-bottom: 10px;
            }

            .kanban-board {
                display: flex;
                gap: 16px;
                min-height: 70vh;
            }

            .kanban-col {
                width: 320px;
                flex: 0 0 320px;
            }

            .kanban-col-header {
                border-radius: .5rem .5rem 0 0;
                padding: 12px;
            }

            .kanban-col-body {
                background: #fff;
                border: 1px solid rgba(0, 0, 0, .08);
                border-top: 0;
                border-radius: 0 0 .5rem .5rem;
                min-height: 320px;
                padding: 10px;
            }

            .lead-column {
                min-height: 280px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .lead-card {
                background: #fff;
                border: 1px solid rgba(0, 0, 0, .08);
                border-radius: .5rem;
                padding: 10px;
                cursor: grab;
            }

            .lead-card:active {
                cursor: grabbing;
            }
        </style>

        <div class="kanban-wrap">
            <div class="kanban-board" id="leadKanbanBoard">

                @foreach ($leadStages as $stage)
                    @php
                        $items = $leads[$stage] ?? collect();
                        $cnt = $items->count();
                        $pct = $total > 0 ? round(($cnt / $total) * 100) : 0;
                        $color = $stageColor($stage);
                    @endphp

                    <div class="kanban-col">
                        <div
                            class="kanban-col-header bg-{{ $color }} bg-opacity-10 border-start border-3 border-{{ $color }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ $stageLabel($stage) }}</div>
                                    <div class="text-muted small">
                                        <span class="stage-pct"
                                            data-stage="{{ $stage }}">{{ $pct }}</span>% of leads
                                    </div>
                                </div>

                                <div class="text-end">
                                    <span class="badge text-bg-{{ $color }} stage-count"
                                        data-stage="{{ $stage }}">
                                        {{ $cnt }}
                                    </span>
                                </div>
                            </div>

                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-{{ $color }} stage-progress"
                                    data-stage="{{ $stage }}" role="progressbar"
                                    style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>

                            <div class="text-muted small mt-1">
                                Drag cards to change stage
                            </div>
                        </div>

                        <div class="kanban-col-body">
                            <div class="lead-column" data-stage="{{ $stage }}">

                                @foreach ($items as $lead)
                                    @php
                                        $next = $lead->next_followup_at
                                            ? \Carbon\Carbon::parse($lead->next_followup_at)
                                            : null;

                                        // Badge color: overdue/due-soon fixed, scheduled uses column color
                                        $followPill = null;
                                        $followText = null;

                                        if ($next) {
                                            if ($next->isPast()) {
                                                $followPill = 'danger';
                                                $followText = 'Overdue';
                                            } elseif ($next->diffInHours(now()) <= 48) {
                                                $followPill = 'warning';
                                                $followText = 'Due soon';
                                            } else {
                                                $followPill = $color; // ✅ match column
                                                $followText = 'Scheduled';
                                            }
                                        }
                                    @endphp

                                    <div class="lead-card border-start border-3 border-{{ $color }}"
                                        data-contact-id="{{ $lead->id }}" data-stage="{{ $stage }}">

                                        <div class="fw-semibold">{{ $lead->name }}</div>
                                        <div class="text-muted small">{{ $lead->email ?? '—' }}</div>

                                        {{-- ✅ Next follow-up badge --}}
                                        @if ($next)
                                            <div class="mt-2">
                                                <span
                                                    class="badge rounded-pill text-bg-{{ $followPill }} {{ $followPill === 'warning' ? 'text-dark' : '' }}">
                                                    {{ $followText }}
                                                </span>
                                                <span class="text-muted small ms-1">{{ $next->diffForHumans() }}</span>
                                            </div>
                                        @endif

                                        <div class="mt-2 d-flex justify-content-between align-items-center">
                                            <a class="btn btn-sm btn-outline-secondary"
                                                href="{{ tenant_route('tenant.leads.edit', ['contact' => $lead->id]) }}">
                                                Edit
                                            </a>

                                            <button type="button" class="btn btn-sm btn-outline-primary btn-qualify"
                                                data-contact-id="{{ $lead->id }}"
                                                data-contact-name="{{ $lead->name }}"
                                                data-action="{{ tenant_route('tenant.leads.qualify', ['contact' => $lead->id]) }}"
                                                data-bs-toggle="modal" data-bs-target="#qualifyModal">
                                                Qualify
                                            </button>

                                        </div>

                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>

    </div>
@endsection

@push('modals')
    @include('tenant.leads.partials.qualify-modal', ['companies' => $companies])
@endpush

@push('scripts')
    <script>
        const tenant = @json($tenant->subdomain);
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function toast(msg) {
            const el = document.getElementById('appToast');
            document.getElementById('appToastBody').textContent = msg;
            bootstrap.Toast.getOrCreateInstance(el, {
                delay: 1800
            }).show();
        }

        function setStageCount(stage, val) {
            const el = document.querySelector(`.stage-count[data-stage="${stage}"]`);
            if (el) el.textContent = String(Math.max(0, val));
        }

        function getStageCount(stage) {
            const el = document.querySelector(`.stage-count[data-stage="${stage}"]`);
            return el ? parseInt(el.textContent || "0", 10) : 0;
        }

        function bumpStage(stage, delta) {
            setStageCount(stage, getStageCount(stage) + delta);
        }

        function refreshStage(stage) {
            const count = getStageCount(stage);
            const totalEl = document.getElementById('totalLeads');
            const total = totalEl ? parseInt(totalEl.textContent || "0", 10) : 0;

            const pct = total > 0 ? Math.round((count / total) * 100) : 0;

            const pctEl = document.querySelector(`.stage-pct[data-stage="${stage}"]`);
            if (pctEl) pctEl.textContent = String(pct);

            const bar = document.querySelector(`.stage-progress[data-stage="${stage}"]`);
            if (bar) bar.style.width = pct + '%';
        }

        async function patchStage(contactId, leadStage) {
            const res = await fetch(`/t/${tenant}/leads/${contactId}/stage`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    lead_stage: leadStage
                })
            });

            if (!res.ok) throw new Error('Request failed');
            const json = await res.json();
            if (!json.ok) throw new Error('Server rejected');
            return json;
        }

        // Sortable (keep your existing functionality)
        document.querySelectorAll('.lead-column').forEach(col => {
            new Sortable(col, {
                group: 'leads',
                animation: 150,
                forceFallback: true,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                draggable: '.lead-card',
                ghostClass: 'opacity-50',

                onAdd: async (evt) => {
                    const card = evt.item;
                    const fromStage = evt.from.dataset.stage;
                    const toStage = evt.to.dataset.stage;
                    const contactId = card.dataset.contactId;

                    if (!fromStage || !toStage || fromStage === toStage) return;

                    // optimistic counters
                    bumpStage(fromStage, -1);
                    bumpStage(toStage, +1);
                    refreshStage(fromStage);
                    refreshStage(toStage);

                    try {
                        await patchStage(contactId, toStage);
                        card.dataset.stage = toStage;
                        toast(`Moved to ${toStage.replaceAll('_',' ')}`);
                    } catch (e) {
                        // revert DOM
                        evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);

                        // revert counters
                        bumpStage(fromStage, +1);
                        bumpStage(toStage, -1);
                        refreshStage(fromStage);
                        refreshStage(toStage);

                        toast('Failed to move. Reverted.');
                    }
                }
            });
        });

        // Qualify modal wiring (kept as-is)
        document.addEventListener('DOMContentLoaded', () => {
            const qualifyForm = document.getElementById('qualifyForm');
            const qualifyLeadName = document.getElementById('qualifyLeadName');
            const qualifyContactId = document.getElementById('qualifyContactId');

            if (!qualifyForm) return;

            document.querySelectorAll('.btn-qualify').forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.dataset.action;
                    const id = btn.dataset.contactId;
                    const name = btn.dataset.contactName;

                    qualifyForm.action = action; // ✅ this prevents posting to /leads/kanban
                    if (qualifyContactId) qualifyContactId.value = id;
                    if (qualifyLeadName) qualifyLeadName.textContent = `Lead: ${name}`;
                });
            });
        });




        const companyMode = document.getElementById('companyMode');
        const attachWrap = document.getElementById('attachCompanyWrap');
        const createWrap = document.getElementById('createCompanyWrap');

        function syncCompanyMode() {
            const mode = companyMode.value;
            attachWrap.classList.toggle('d-none', mode !== 'attach');
            createWrap.classList.toggle('d-none', mode !== 'create');
        }
        companyMode.addEventListener('change', syncCompanyMode);
        syncCompanyMode();

        const createDeal = document.getElementById('createDeal');
        const dealFields = document.getElementById('dealFields');
        createDeal.addEventListener('change', () => {
            dealFields.classList.toggle('d-none', !createDeal.checked);
        });
    </script>
@endpush
