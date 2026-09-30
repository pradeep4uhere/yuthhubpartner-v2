@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">
        <header class="admin-users-header">
            <a href="{{ route('admin.dashboard') }}">&larr; Dashboard</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>

        <section class="admin-partners-content">
            <div class="admin-partners-heading">
                <div>
                    <p class="admin-auth-eyebrow">PLACEMENT NETWORK</p>
                    <h1>Placement users</h1>
                    <p class="admin-users-intro">Manage placement organisation accounts and portal access.</p>
                </div>
                @can('partner_placement_users.create')
                    <a class="admin-partners-primary" href="{{ route('admin.partner-placement-users.create') }}">Add placement user</a>
                @endcan
            </div>

            @if (session('status'))
                <div class="admin-users-success" role="status">{{ session('status') }}</div>
            @endif

            <section class="admin-partners-panel">
                <form method="GET" action="{{ route('admin.partner-placement-users.index') }}" class="admin-partners-filters">
                    <label class="admin-partners-search">
                        <span class="visually-hidden">Search placement users</span>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, organisation, email or code">
                    </label>
                    <label>
                        <span class="visually-hidden">Filter by status</span>
                        <select name="status">
                            <option value="">All statuses</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </label>
                    <button type="submit">Apply</button>
                    @if (! empty($filters))
                        <a class="admin-partners-reset" href="{{ route('admin.partner-placement-users.index') }}">Clear</a>
                    @endif
                </form>

                <div class="admin-partners-table-wrap">
                    <table class="admin-partners-table">
                        <thead>
                            <tr><th>PP code</th><th>Name</th><th>Placement organisation</th><th>Contact</th><th>Email</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($placementUsers as $placementUser)
                                <tr>
                                    <td><strong>{{ $placementUser->pp_code }}</strong></td>
                                    <td>{{ $placementUser->name }}</td>
                                    <td>{{ $placementUser->partner_placement_name }}</td>
                                    <td>{{ $placementUser->contact_number ?: '—' }}</td>
                                    <td>{{ $placementUser->email }}</td>
                                    <td><span class="admin-partner-status admin-partner-status--{{ $placementUser->status }}">{{ ucfirst($placementUser->status) }}</span></td>
                                    <td>
                                        <div class="admin-partner-actions">
                                            @can('partner_placement_users.update')
                                                <a href="{{ route('admin.partner-placement-users.edit', $placementUser) }}">Edit</a>
                                            @endcan
                                            @can('partner_placement_users.delete')
                                                <form method="POST" action="{{ route('admin.partner-placement-users.destroy', $placementUser) }}" onsubmit="return confirm('Delete this placement user?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="admin-partners-empty" colspan="7">No placement users found. Add one to get started.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <footer class="admin-partners-footer">
                    <span>Showing {{ $placementUsers->firstItem() ?? 0 }}–{{ $placementUsers->lastItem() ?? 0 }} of {{ $placementUsers->total() }} placement users</span>
                    {{ $placementUsers->links() }}
                </footer>
            </section>
        </section>
    </main>
@endsection
