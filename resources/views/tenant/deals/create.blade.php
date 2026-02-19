@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0" style="max-width: 900px;">
        <h1 class="h3 mb-3">Create Deal</h1>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.deals.store') }}">
                    @csrf

                    @if ($lead)
                        <input type="hidden" name="lead_contact_id" value="{{ $lead->id }}">
                        <div class="alert alert-info">
                            Creating deal from lead: <strong>{{ $lead->name }}</strong>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input class="form-control" name="title" required
                            value="{{ old('title', $lead ? 'Deal for ' . $lead->name : '') }}">
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount</label>
                            <input class="form-control" name="amount" type="number" step="0.01"
                                value="{{ old('amount', 0) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expected close date</label>
                            <input class="form-control" name="expected_close_date" type="date"
                                value="{{ old('expected_close_date') }}">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pipeline</label>
                            <select class="form-select" name="pipeline_id" required>
                                @foreach ($pipelines as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stage</label>
                            <select class="form-select" name="stage_id" required>
                                {{-- simplest: you can load stages via JS later based on selected pipeline --}}
                                @php
                                    $first = $pipelines->first();
                                    $stages = $first
                                        ? \App\Models\PipelineStage::where('pipeline_id', $first->id)
                                            ->orderBy('position')
                                            ->get()
                                        : collect();
                                @endphp
                                @foreach ($stages as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if (!$lead)
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company</label>
                                <select class="form-select" name="company_id">
                                    <option value="">-- select --</option>
                                    @foreach ($companies as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Required unless creating from a lead.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Primary Contact (optional)</label>
                                <input class="form-control" name="primary_contact_id"
                                    placeholder="(we can make this a dropdown next)">
                            </div>
                        </div>
                    @endif

                    <button class="btn btn-primary">Create Deal</button>
                    <a href="{{ tenant_route('tenant.deals.index') }}" class="btn btn-light">Back</a>

                </form>
            </div>
        </div>
    </div>
@endsection
