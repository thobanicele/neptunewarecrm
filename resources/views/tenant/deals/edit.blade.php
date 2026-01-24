@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Edit Deal</h3>
        <a href="{{ tenant_route('tenant.deals.show', ['tenant' => $tenant, 'deal' => $deal]) }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ tenant_route('tenant.deals.update', ['tenant' => $tenant, 'deal' => $deal]) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input class="form-control" name="title" value="{{ old('title', $deal->title) }}" required>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input class="form-control" name="amount" type="number" step="0.01"
                               value="{{ old('amount', $deal->amount) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Stage</label>
                        <select class="form-select" name="stage_id" required>
                            @foreach($stages as $s)
                                <option value="{{ $s->id }}" @selected(old('stage_id', $deal->stage_id) == $s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Expected close date</label>
                        <input class="form-control" name="expected_close_date" type="date"
                               value="{{ old('expected_close_date', optional($deal->expected_close_date)->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="4">{{ old('notes', $deal->notes) }}</textarea>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
