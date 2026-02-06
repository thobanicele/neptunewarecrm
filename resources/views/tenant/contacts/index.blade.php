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
                            <th>Action</th>
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
                                <td class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-secondary"
                                        href="{{ tenant_route('tenant.contacts.show', $c) }}">
                                        View
                                    </a>

                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ tenant_route('tenant.contacts.edit', $c) }}">
                                        Edit
                                    </a>

                                    <form method="POST" action="{{ tenant_route('tenant.contacts.destroy', $c) }}"
                                        onsubmit="return confirm('Delete this contact?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">{{ $contacts->links() }}</div>
            </div>
        </div>
    </div>
@endsection
