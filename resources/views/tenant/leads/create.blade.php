@extends('layouts.app')

@section('content')
<div class="container-fluid p-0" style="max-width:700px;">
    <h1 class="h3 mb-3">Add Lead</h1>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ tenant_route('tenant.leads.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input class="form-control" name="name" required value="{{ old('name') }}">
                </div>

                <div class="row g-2">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" name="email" type="email" value="{{ old('email') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                </div>

                <button class="btn btn-primary">Create Lead</button>
                <a class="btn btn-light" href="{{ tenant_route('tenant.leads.index') }}">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
