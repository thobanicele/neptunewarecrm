@php
    $items = $activities ?? collect();
    $modalId = 'activityModal_' . md5(($subject_type ?? 'x') . '_' . ($subject_id ?? '0'));
@endphp

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="fw-semibold">Activity Timeline</div>

        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
            + Add Activity
        </button>
    </div>

    <div class="card-body">
        @if ($items->isEmpty())
            <div class="text-muted">No activities yet.</div>
        @else
            <div class="d-flex flex-column gap-2">
                @foreach ($items as $a)
                    @php
                        $isDone = !is_null($a->done_at);
                        $isOverdue = !$isDone && $a->due_at && $a->due_at->isPast();
                    @endphp

                    <div class="border rounded p-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    <span class="badge text-bg-light text-dark me-2">{{ strtoupper($a->type) }}</span>

                                    {{ $a->title ?? 'Activity' }}

                                    @if ($isDone)
                                        <span class="badge text-bg-success ms-2">Done</span>
                                    @elseif($isOverdue)
                                        <span class="badge text-bg-danger ms-2">Overdue</span>
                                    @elseif($a->due_at)
                                        <span class="badge text-bg-warning ms-2">Due</span>
                                    @endif
                                </div>

                                @if ($a->body)
                                    <div class="text-muted small mt-1" style="white-space: pre-wrap;">
                                        {{ $a->body }}</div>
                                @endif

                                <div class="text-muted small mt-2">
                                    By {{ $a->user?->name ?? '—' }}
                                    • {{ $a->created_at->diffForHumans() }}
                                    @if ($a->due_at)
                                        • Due {{ $a->due_at->format('Y-m-d H:i') }}
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-2">
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
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- ...card/timeline list above stays the same... --}}

@push('modals')
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ tenant_route('tenant.activities.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="subject_type" value="{{ $subject_type }}">
                    <input type="hidden" name="subject_id" value="{{ $subject_id }}">

                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="call">Call</option>
                            <option value="meeting">Meeting</option>
                            <option value="email">Email</option>
                            <option value="note">Note</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input class="form-control" name="title" placeholder="e.g. Follow-up on quote"
                            value="{{ old('title') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Details</label>
                        <textarea class="form-control" name="body" rows="3" placeholder="Notes...">{{ old('body') }}</textarea>
                    </div>

                    {{-- ✅ Follow-up toggle --}}
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="{{ $modalId }}_is_followup"
                            name="is_followup" value="1" {{ old('is_followup') ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $modalId }}_is_followup">
                            This is a follow-up
                        </label>
                    </div>

                    {{-- ✅ Due date (shown only when follow-up is checked) --}}
                    <div id="{{ $modalId }}_due_wrap" class="mb-0 d-none">
                        <label class="form-label">Due at *</label>
                        <input type="datetime-local" class="form-control" id="{{ $modalId }}_due_at" name="due_at"
                            value="{{ old('due_at') }}">
                        <div class="form-text">Required for follow-ups.</div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalId = @json($modalId);
            const chk = document.getElementById(modalId + '_is_followup');
            const wrap = document.getElementById(modalId + '_due_wrap');
            const due = document.getElementById(modalId + '_due_at');

            if (!chk || !wrap || !due) return;

            function sync() {
                const on = chk.checked;
                wrap.classList.toggle('d-none', !on);
                due.required = on;
                if (!on) due.value = ''; // prevents accidentally submitting stale due_at
            }

            chk.addEventListener('change', sync);
            sync();
        });
    </script>
@endpush
