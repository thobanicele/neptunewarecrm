@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-success">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            @push('scripts')
                <script>
                    setTimeout(() => {
                        const el = document.getElementById('flash-success');
                        if (!el) return;
                        const alert = bootstrap.Alert.getOrCreateInstance(el);
                        alert.close();
                    }, 3500);
                </script>
            @endpush
        @endif
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Companies</h1>
            <a class="btn btn-primary" href="{{ tenant_route('tenant.companies.create') }}">Add Company</a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th class="text-end" style="width: 210px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $c)
                            <tr>
                                <td>
                                    <a href="{{ tenant_route('tenant.companies.show', $c) }}">{{ $c->name }}</a>
                                </td>
                                <td>{{ $c->type }}</td>
                                <td>{{ $c->email ?? '—' }}</td>
                                <td>{{ $c->phone ?? '—' }}</td>
                                <td class="text-end">
                                    <div class="btn-group" role="group" aria-label="Company actions">
                                        <a class="btn btn-sm btn-light"
                                            href="{{ tenant_route('tenant.companies.show', $c) }}">
                                            View
                                        </a>

                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.companies.edit', $c) }}">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ tenant_route('tenant.companies.destroy', $c) }}"
                                            onsubmit="return confirm('Delete {{ addslashes($c->name) }}? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No companies found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-3">
                    {{ $companies->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
