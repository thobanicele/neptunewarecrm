@extends('layouts.app')
@section('title', 'Super Admin Dashboard')

@section('content')
    <h1>All Tenants</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Subdomain</th>
                <th>Plan</th>
                <th>Users</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tenants as $tenant)
                <tr>
                    <td>{{ $tenant->name }}</td>
                    <td>{{ $tenant->subdomain }}</td>
                    <td>{{ $tenant->plan }}</td>
                    <td>{{ $tenant->users()->count() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
