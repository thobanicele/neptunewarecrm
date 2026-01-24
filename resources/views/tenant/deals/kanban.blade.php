@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        @php
            $stageColor = fn($name) => match ($name) {
                'New Lead' => 'secondary',
                'Qualified' => 'info',
                'Proposal Sent' => 'warning',
                'Negotiation' => 'primary',
                'Won' => 'success',
                'Lost' => 'danger',
                default => 'dark',
            };

            $isClosedStage = fn($name) => in_array($name, ['Won', 'Lost']);
        @endphp

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Kanban Pipeline</h3>
                <div class="text-muted small">
                    Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.deals.index', ['tenant' => $tenant->subdomain]) }}"
                    class="btn btn-outline-secondary">
                    List view
                </a>
                <a href="{{ tenant_route('tenant.deals.create', ['tenant' => $tenant->subdomain]) }}" class="btn btn-primary">
                    + Add Deal
                </a>
            </div>
        </div>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Toast container (AdminKit/Bootstrap) --}}
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

            .kanban-dropzone {
                min-height: 280px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .kanban-card {
                background: #fff;
                border: 1px solid rgba(0, 0, 0, .08);
                border-radius: .5rem;
                padding: 10px;
                cursor: grab;
            }

            .kanban-card.is-locked {
                opacity: .75;
                cursor: not-allowed;
            }

            .kanban-card:active {
                cursor: grabbing;
            }

            /* tiny “saving” indicator */
            .kanban-card.is-saving {
                opacity: .85;
            }

            .kanban-saving-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                display: inline-block;
                margin-left: 8px;
                animation: pulse 1s infinite;
            }

            @keyframes pulse {
                0% {
                    opacity: .2;
                    transform: scale(1);
                }

                50% {
                    opacity: 1;
                    transform: scale(1.25);
                }

                100% {
                    opacity: .2;
                    transform: scale(1);
                }
            }
        </style>

        <div class="kanban-wrap">
            <div class="kanban-board" id="kanbanBoard">

                @foreach ($stages as $stage)
                    @php
                        $cards = $deals[$stage->id] ?? collect();
                        $row = $stats[$stage->id] ?? null;
                        $cnt = (int) ($row->cnt ?? 0);
                        $sum = (float) ($row->total_amount ?? 0);
                        $pct = $totalPipelineAmount > 0 ? round(($sum / $totalPipelineAmount) * 100) : 0;
                        $color = $stageColor($stage->name);
                        $closed = $isClosedStage($stage->name);
                    @endphp

                    <div class="kanban-col" data-stage-id="{{ $stage->id }}" data-stage-name="{{ $stage->name }}"
                        data-stage-color="{{ $color }}">
                        <div
                            class="kanban-col-header bg-{{ $color }} bg-opacity-10 border-start border-3 border-{{ $color }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ ucfirst($stage->name) }}</div>
                                    <div class="text-muted small">
                                        R <span class="stage-sum"
                                            data-stage-id="{{ $stage->id }}">{{ number_format($sum, 2) }}</span>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <span class="badge text-bg-{{ $color }} stage-count"
                                        data-stage-id="{{ $stage->id }}">
                                        {{ $cnt }}
                                    </span>
                                    @if ($closed)
                                        <div class="text-muted small mt-1">Locked</div>
                                    @endif
                                </div>
                            </div>

                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-{{ $color }} stage-progress"
                                    data-stage-id="{{ $stage->id }}" role="progressbar"
                                    style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>

                            <div class="text-muted small mt-1">
                                <span class="stage-pct" data-stage-id="{{ $stage->id }}">{{ $pct }}</span>% of
                                pipeline
                            </div>
                        </div>

                        <div class="kanban-col-body">
                            <div class="kanban-dropzone" data-stage-id="{{ $stage->id }}">
                                @foreach ($cards as $deal)
                                    @php
                                        $dealLocked = $closed;
                                        $next = $deal->next_followup_at
                                            ? \Carbon\Carbon::parse($deal->next_followup_at)
                                            : null;
                                    @endphp

                                    <div class="kanban-card border-start border-3 border-{{ $color }} {{ $dealLocked ? 'is-locked' : '' }}"
                                        data-deal-id="{{ $deal->id }}" data-amount="{{ (float) $deal->amount }}"
                                        data-stage-id="{{ $stage->id }}" data-stage-name="{{ $stage->name }}"
                                        draggable="{{ $dealLocked ? 'false' : 'true' }}"
                                        title="{{ $dealLocked ? 'This deal is closed and locked.' : 'Drag to move stage' }}">

                                        <div class="fw-semibold">
                                            {{ $deal->title }}
                                            <span class="kanban-saving-dot bg-{{ $color }} d-none"
                                                data-saving-dot="{{ $deal->id }}"></span>
                                        </div>

                                        <div class="text-muted small">
                                            R {{ number_format((float) $deal->amount, 2) }}
                                        </div>

                                        {{-- ✅ Next follow-up badge --}}
                                        @if ($next)
                                            <div class="mt-2">
                                                @if ($next->isPast())
                                                    <span class="badge rounded-pill text-bg-danger">Overdue</span>
                                                @elseif($next->diffInHours(now()) <= 48)
                                                    <span class="badge rounded-pill text-bg-warning text-dark">Due
                                                        soon</span>
                                                @else
                                                    <span class="badge rounded-pill text-bg-info">Scheduled</span>
                                                @endif
                                                <span class="text-muted small ms-1">{{ $next->diffForHumans() }}</span>
                                            </div>
                                        @endif

                                        <div class="mt-2 d-flex justify-content-between align-items-center">
                                            <a class="small"
                                                href="{{ tenant_route('tenant.deals.show', ['tenant' => $tenant->subdomain, 'deal' => $deal->id]) }}">
                                                View
                                            </a>
                                            <span class="badge text-bg-light text-dark">#{{ $deal->id }}</span>
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


@push('scripts')
    <script>
        (function() {
            const tenant = @json($tenant->subdomain);
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Bootstrp Toast
            const toastEl = document.getElementById('appToast');
            const toastBody = document.getElementById('appToastBody');
            const toast = (typeof bootstrap !== 'undefined' && bootstrap.Toast) ?
                bootstrap.Toast.getOrCreateInstance(toastEl, {
                    delay: 1400
                }) :
                null;

            function showToast(msg) {
                if (!toast || !toastBody) return;
                toastBody.textContent = msg;
                toast.show();
            }

            function money(n) {
                const v = Number(n || 0);
                return v.toLocaleString('en-ZA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function getStageCountEl(stageId) {
                return document.querySelector(`.stage-count[data-stage-id="${stageId}"]`);
            }

            function getStageSumEl(stageId) {
                return document.querySelector(`.stage-sum[data-stage-id="${stageId}"]`);
            }

            function getStagePctEl(stageId) {
                return document.querySelector(`.stage-pct[data-stage-id="${stageId}"]`);
            }

            function getStageProgressEl(stageId) {
                return document.querySelector(`.stage-progress[data-stage-id="${stageId}"]`);
            }

            function setCount(stageId, delta) {
                const badge = getStageCountEl(stageId);
                if (!badge) return;
                const v = parseInt(badge.textContent || "0", 10);
                badge.textContent = Math.max(0, v + delta);
            }

            function stageSum(stageId) {
                const zone = document.querySelector(`.kanban-dropzone[data-stage-id="${stageId}"]`);
                if (!zone) return 0;
                let sum = 0;
                zone.querySelectorAll('.kanban-card').forEach(card => {
                    sum += Number(card.dataset.amount || 0);
                });
                return sum;
            }

            function totalPipelineSum() {
                let sum = 0;
                document.querySelectorAll('.kanban-card').forEach(card => sum += Number(card.dataset.amount || 0));
                return sum;
            }

            function refreshStageHeader(stageId) {
                const sum = stageSum(stageId);
                const sumEl = getStageSumEl(stageId);
                if (sumEl) sumEl.textContent = money(sum);

                const total = totalPipelineSum();
                const pct = total > 0 ? Math.round((sum / total) * 100) : 0;

                const pctEl = getStagePctEl(stageId);
                if (pctEl) pctEl.textContent = pct;

                const bar = getStageProgressEl(stageId);
                if (bar) {
                    bar.style.width = `${pct}%`;
                    bar.setAttribute('aria-valuenow', String(pct));
                }
            }

            function refreshAllHeaders() {
                document.querySelectorAll('.kanban-col').forEach(col => {
                    const stageId = col.dataset.stageId;
                    refreshStageHeader(stageId);
                });
            }

            // -------------------------
            // HTML5 Drag & Drop
            // -------------------------
            let dragged = null;
            let fromCol = null;
            let fromStageId = null;
            let fromStageName = null;

            const CLOSED = new Set(['won', 'lost']); // normalized

            function bindDragCards() {
                document.querySelectorAll('.kanban-card[draggable="true"]').forEach(card => {
                    card.removeEventListener('dragstart', onDragStart);
                    card.addEventListener('dragstart', onDragStart);
                });
            }

            function onDragStart(e) {
                dragged = e.currentTarget;
                fromCol = dragged.closest('.kanban-col');
                fromStageId = String(dragged.dataset.stageId || '');
                fromStageName = dragged.dataset.stageName || '';

                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', dragged.dataset.dealId || '');
            }

            function bindDropZones() {
                document.querySelectorAll('.kanban-dropzone, .kanban-col-body').forEach(zone => {
                    // avoid double-binding
                    if (zone.dataset.bound === '1') return;
                    zone.dataset.bound = '1';

                    zone.addEventListener('dragenter', (e) => e.preventDefault());
                    zone.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
                    });
                    zone.addEventListener('drop', onDrop);
                });
            }

            function setSaving(dealId, isSaving) {
                const dot = document.querySelector(`[data-saving-dot="${dealId}"]`);
                if (dot) dot.classList.toggle('d-none', !isSaving);

                const card = document.querySelector(`.kanban-card[data-deal-id="${dealId}"]`);
                if (card) card.classList.toggle('is-saving', !!isSaving);
            }

            async function onDrop(e) {
                e.preventDefault();
                if (!dragged) return;

                let zone = e.currentTarget;
                if (zone.classList.contains('kanban-col-body')) {
                    zone = zone.querySelector('.kanban-dropzone') || zone;
                }

                const toCol = zone.closest('.kanban-col');
                const toStageId = String(toCol?.dataset.stageId || '');
                const toStageName = toCol?.dataset.stageName || '';
                const dealId = dragged.dataset.dealId;

                if (!dealId || !toStageId) return;

                // no-op drop (same stage)
                if (toStageId === fromStageId) {
                    dragged = null;
                    return;
                }

                // optimistic UI
                zone.prepend(dragged);

                dragged.dataset.stageId = toStageId;
                dragged.dataset.stageName = toStageName;

                setCount(fromStageId, -1);
                setCount(toStageId, +1);

                refreshStageHeader(fromStageId);
                refreshStageHeader(toStageId);

                showToast(`Saving… (#${dealId})`);
                setSaving(dealId, true);

                // lock if moved into won/lost
                const toLower = (toStageName || '').toString().trim().toLowerCase();
                const isClosed = CLOSED.has(toLower);

                if (isClosed) {
                    dragged.setAttribute('draggable', 'false');
                    dragged.classList.add('is-locked');
                    dragged.title = 'This deal is closed and locked.';
                }

                try {
                    const res = await fetch(`/t/${tenant}/deals/${dealId}/stage`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            stage_id: parseInt(toStageId, 10)
                        })
                    });

                    const json = await res.json().catch(() => ({}));
                    if (!res.ok || json.ok === false) {
                        throw new Error(json.message || 'Server rejected the update');
                    }

                    showToast(`Moved to ${toStageName} ✅`);
                } catch (err) {
                    // revert UI
                    const fromZone = fromCol.querySelector('.kanban-dropzone') || fromCol.querySelector(
                        '.kanban-col-body');
                    fromZone.prepend(dragged);

                    dragged.dataset.stageId = fromStageId;
                    dragged.dataset.stageName = fromStageName;

                    setCount(fromStageId, +1);
                    setCount(toStageId, -1);

                    refreshStageHeader(fromStageId);
                    refreshStageHeader(toStageId);

                    // unlock revert
                    dragged.setAttribute('draggable', 'true');
                    dragged.classList.remove('is-locked');
                    dragged.title = 'Drag to move stage';

                    showToast(`Failed — reverted ❌`);
                } finally {
                    setSaving(dealId, false);

                    dragged = null;
                    fromCol = null;
                    fromStageId = null;
                    fromStageName = null;

                    // rebind after DOM move
                    bindDragCards();
                }
            }

            // init
            bindDragCards();
            bindDropZones();
            refreshAllHeaders();
        })();
    </script>
@endpush
