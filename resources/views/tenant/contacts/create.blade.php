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

                    {{-- Company (Select2 AJAX) --}}
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select class="form-select js-select2" name="company_id" required
                            data-placeholder="Search company..." data-allow-clear="1"
                            data-ajax-url="{{ tenant_route('tenant.api.select2.search', ['resource' => 'companies']) }}"
                            data-min-input="2" data-delay="250">
                            <option value=""></option>

                            {{-- Preselect after validation error --}}
                            @if (old('company_id'))
                                <option value="{{ old('company_id') }}" selected>
                                    {{ old('company_id') }}
                                </option>
                            @endif
                        </select>
                        <div class="form-text">Start typing to search companies.</div>
                    </div>

                    {{-- Lifecycle (Select2 static) --}}
                    <div class="mb-3">
                        <label class="form-label">Lifecycle</label>
                        <select class="form-select js-select2" name="lifecycle_stage" required
                            data-placeholder="Select lifecycle" data-allow-clear="0">
                            <option value=""></option>
                            <option value="qualified" @selected(old('lifecycle_stage', 'qualified') === 'qualified')>qualified</option>
                            <option value="customer" @selected(old('lifecycle_stage') === 'customer')>customer</option>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initSelect2) {
                window.initSelect2(document);
            } else if (window.jQuery) {
                window.jQuery('.js-select2').select2({
                    width: '100%'
                });
            }
        });
    </script>
@endpush
