@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0" style="max-width:800px;">
        <h1 class="h3 mb-3">Add Contact</h1>

        <div class="card">
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ tenant_route('tenant.contacts.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select class="form-select" name="company_id" required>
                            <option value="">-- select --</option>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lifecycle</label>
                        <select class="form-select" name="lifecycle_stage" required>
                            <option value="qualified">qualified</option>
                            <option value="customer">customer</option>
                        </select>
                    </div>

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

                    <button class="btn btn-primary">Create Contact</button>
                    <a class="btn btn-light" href="{{ tenant_route('tenant.contacts.index') }}">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
