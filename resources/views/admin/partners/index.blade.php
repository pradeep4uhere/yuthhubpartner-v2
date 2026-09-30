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
                    <p class="admin-auth-eyebrow">PARTNER DIRECTORY</p>
                    <h1>Partners</h1>
                    <p class="admin-users-intro">Manage partner organisations and their contact details.</p>
                </div>
                @can('partners.create')
                    <a class="admin-partners-primary" href="{{ route('admin.partners.create') }}">Add partner</a>
                @endcan
            </div>

            @if (session('status'))
                <div class="admin-users-success" role="status">{{ session('status') }}</div>
            @endif
            @error('partner')
                <div class="admin-auth-error" role="alert">{{ $message }}</div>
            @enderror

            <section class="admin-partners-panel">
                <form method="GET" action="{{ route('admin.partners.index') }}" class="admin-partners-filters">
                    <label class="admin-partners-search">
                        <span class="visually-hidden">Search partners</span>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, code, email or phone">
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
                        <a class="admin-partners-reset" href="{{ route('admin.partners.index') }}">Clear</a>
                    @endif
                </form>

                <div class="admin-partners-table-wrap">
                    <table class="admin-partners-table">
                        <thead>
                            <tr>
                                <th>Partner code</th>
                                <th>Name</th>
                                <th>Contact number</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($partners as $partner)
                                <tr>
                                    <td><strong>{{ $partner->partner_code }}</strong></td>
                                    <td>{{ $partner->name }}</td>
                                    <td>{{ $partner->contact_number ?: '—' }}</td>
                                    <td>{{ $partner->email ?: '—' }}</td>
                                    <td><span class="admin-partner-status admin-partner-status--{{ $partner->status }}">{{ ucfirst($partner->status) }}</span></td>
                                    <td>
                                        <div class="admin-partner-actions">
                                            @can('partners.update')
                                                <a href="{{ route('admin.partners.edit', $partner) }}">Edit</a>
                                            @endcan
                                            @can('partners.delete')
                                                <form method="POST" action="{{ route('admin.partners.destroy', $partner) }}" onsubmit="return confirm('Delete this partner?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="admin-partners-empty" colspan="6">No partners found. Add a partner to get started.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <footer class="admin-partners-footer">
                    <span>Showing {{ $partners->firstItem() ?? 0 }}–{{ $partners->lastItem() ?? 0 }} of {{ $partners->total() }} partners</span>
                    {{ $partners->links() }}
                </footer>
            </section>
        </section>
    </main>
@endsection
