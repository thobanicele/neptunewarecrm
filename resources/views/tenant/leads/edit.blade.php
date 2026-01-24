@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="h3 mb-0">Edit Lead</h1>
                <div class="text-muted small">{{ $contact->name }}</div>
            </div>

            <a href="{{ tenant_route('tenant.leads.index') }}" class="btn btn-light">Back</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix the errors below:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.leads.update', ['contact' => $contact->id]) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $contact->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lead stage *</label>
                            <select name="lead_stage" class="form-select @error('lead_stage') is-invalid @enderror"
                                required>
                                <option value="">-- choose --</option>
                                @foreach ($leadStages as $stage)
                                    <option value="{{ $stage }}" @selected(old('lead_stage', $contact->lead_stage) === $stage)>
                                        {{ ucfirst(str_replace('_', ' ', $stage)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_stage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $contact->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $contact->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Optional fields: only keep if you actually have these DB columns --}}
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('contacts', 'source'))
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Source</label>
                                <input type="text" name="source"
                                    class="form-control @error('source') is-invalid @enderror"
                                    value="{{ old('source', $contact->source) }}"
                                    placeholder="e.g. WhatsApp, Website, Referral">
                                @error('source')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Add any notes...">{{ old('notes', $contact->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Save changes</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.leads.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ✅ Polymorphic activities timeline (no duplicates; modal id is unique per subject) --}}
        @include('tenant.activities._timeline', [
            'activities' => $activities,
            'subject_type' => 'contact',
            'subject_id' => $contact->id,
        ])

    </div>
@endsection
