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
                    <p class="admin-auth-eyebrow">PARTNER NETWORK</p>
                    <h1>Partner centers</h1>
                    <p class="admin-users-intro">Manage centers and the partner organisations they belong to.</p>
                </div>
                @can('partner_centers.create')
                    <a class="admin-partners-primary" href="{{ route('admin.partner-centers.create') }}">Add center</a>
                @endcan
            </div>

            @if (session('status'))
                <div class="admin-users-success" role="status">{{ session('status') }}</div>
            @endif

            <section class="admin-partners-panel">
                <form method="GET" action="{{ route('admin.partner-centers.index') }}" class="admin-partners-filters">
                    <label class="admin-partners-search">
                        <span class="visually-hidden">Search partner centers</span>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search center, code, parent or contact">
                    </label>
                    <label>
                        <span class="visually-hidden">Filter by partner</span>
                        <select name="partner_id">
                            <option value="">All partners</option>
                            @foreach ($partners as $partner)
                                <option value="{{ $partner->id }}" @selected((string) ($filters['partner_id'] ?? '') === (string) $partner->id)>{{ $partner->name }}</option>
                            @endforeach
                        </select>
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
                        <a class="admin-partners-reset" href="{{ route('admin.partner-centers.index') }}">Clear</a>
                    @endif
                </form>

                <div class="admin-partners-table-wrap">
                    <table class="admin-partners-table">
                        <thead>
                            <tr>
                                <th>Center code</th>
                                <th>Center name</th>
                                <th>Partner</th>
                                <th>Contact number</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($partnerCenters as $partnerCenter)
                                <tr>
                                    <td><strong>{{ $partnerCenter->partner_code }}</strong></td>
                                    <td>{{ $partnerCenter->name }}</td>
                                    <td>{{ $partnerCenter->partner->name }}</td>
                                    <td>{{ $partnerCenter->contact_number ?: '—' }}</td>
                                    <td>{{ $partnerCenter->email ?: '—' }}</td>
                                    <td><span class="admin-partner-status admin-partner-status--{{ $partnerCenter->status }}">{{ ucfirst($partnerCenter->status) }}</span></td>
                                    <td>
                                        <div class="admin-partner-actions">
                                            @can('partner_centers.update')
                                                <a href="{{ route('admin.partner-centers.edit', $partnerCenter) }}">Edit</a>
                                            @endcan
                                            @can('partner_centers.delete')
                                                <form method="POST" action="{{ route('admin.partner-centers.destroy', $partnerCenter) }}" onsubmit="return confirm('Delete this partner center?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="admin-partners-empty" colspan="7">No partner centers found. Add a center to get started.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <footer class="admin-partners-footer">
                    <span>Showing {{ $partnerCenters->firstItem() ?? 0 }}–{{ $partnerCenters->lastItem() ?? 0 }} of {{ $partnerCenters->total() }} centers</span>
                    {{ $partnerCenters->links() }}
                </footer>
            </section>
        </section>
    </main>
@endsection
