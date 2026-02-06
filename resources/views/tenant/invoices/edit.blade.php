@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0" style="max-width:800px;">
        <h1 class="h3 mb-3">Edit Invoice</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.invoices.update', $invoice) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Reference (editable)</label>
                        <input class="form-control" name="reference" value="{{ old('reference', $invoice->reference) }}">
                        <div class="text-muted small mt-1">Defaults to the Quote Number when converted; can be changed while
                            Draft.</div>
                    </div>

                    <button class="btn btn-primary">Save</button>
                    <a class="btn btn-light" href="{{ tenant_route('tenant.invoices.show', $invoice) }}">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
