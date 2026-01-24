@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Contacts</h1>

            @if (app()->bound('tenant'))
                <a href="{{ tenant_route('tenant.contacts.create') }}" class="btn btn-primary">+ Add Contact</a>
            @endif
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Stage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contacts as $c)
                            <tr>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->email ?? '—' }}</td>
                                <td>{{ $c->phone ?? '—' }}</td>
                                <td>{{ $c->company?->name ?? '—' }}</td>
                                <td>{{ ucfirst($c->lifecycle_stage) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">{{ $contacts->links() }}</div>
            </div>
        </div>
    </div>
@endsection
