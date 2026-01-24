@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Companies</h1>
        <a class="btn btn-primary" href="{{ tenant_route('tenant.companies.create') }}">Add Company</a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table mb-0">
                <thead><tr><th>Name</th><th>Type</th><th>Email</th><th>Phone</th></tr></thead>
                <tbody>
                    @foreach($companies as $c)
                        <tr>
                            <td><a href="{{ tenant_route('tenant.companies.show', $c) }}">{{ $c->name }}</a></td>
                            <td>{{ $c->type }}</td>
                            <td>{{ $c->email ?? '—' }}</td>
                            <td>{{ $c->phone ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $companies->links() }}</div>
        </div>
    </div>
</div>
@endsection
