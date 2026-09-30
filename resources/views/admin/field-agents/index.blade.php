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
                    <p class="admin-auth-eyebrow">FIELD OPERATIONS</p>
                    <h1>Field agents</h1>
                    <p class="admin-users-intro">Manage field teams and their organisation assignments.</p>
                </div>
                @can('field_agents.create')
                    <a class="admin-partners-primary" href="{{ route('admin.field-agents.create') }}">Add field agent</a>
                @endcan
            </div>

            @if (session('status'))
                <div class="admin-users-success" role="status">{{ session('status') }}</div>
            @endif

            <section class="admin-partners-panel">
                <form method="GET" action="{{ route('admin.field-agents.index') }}" class="admin-partners-filters">
                    <label class="admin-partners-search">
                        <span class="visually-hidden">Search field agents</span>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email, CSC or Sakhi ID">
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
                        <a class="admin-partners-reset" href="{{ route('admin.field-agents.index') }}">Clear</a>
                    @endif
                </form>

                <div class="admin-partners-table-wrap">
                    <table class="admin-partners-table">
                        <thead><tr><th>Agent</th><th>CSC / Sakhi ID</th><th>Partner</th><th>Contact Number</th><th>Placement partner</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse ($fieldAgents as $fieldAgent)
                                <tr>
                                    <td><strong>{{ $fieldAgent->name }}</strong><br><small>{{ $fieldAgent->email }}</small></td>
                                    <td>{{ $fieldAgent->csc_id ?: '—' }} <br><small> {{ $fieldAgent->sakhi_id ?: '—' }}</small></td>
                                    <td></strong>{{ $fieldAgent->partner->name }}</strong><br><small>{{ $fieldAgent->partnerCenter->name }}</td>
                                    <td>{{ $fieldAgent->contact_number }}</td>
                                    <td>{{ optional($fieldAgent->placementPartnerUser)->partner_placement_name ?? '-' }}</td>
                                    <td>{{ $fieldAgent->district }}, {{ $fieldAgent->state }}</td>
                                    <td><span class="admin-partner-status admin-partner-status--{{ $fieldAgent->status }}">{{ ucfirst($fieldAgent->status) }}</span></td>
                                    <td>
                                        <div class="admin-partner-actions">
                                            @can('field_agents.update')
                                                <a href="{{ route('admin.field-agents.edit', $fieldAgent) }}">Edit</a>
                                            @endcan
                                            @can('field_agents.delete')
                                                <form method="POST" action="{{ route('admin.field-agents.destroy', $fieldAgent) }}" onsubmit="return confirm('Delete this field agent?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="admin-partners-empty" colspan="8">No field agents found. Add a field agent to get started.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <footer class="admin-partners-footer">
                    <span>Showing {{ $fieldAgents->firstItem() ?? 0 }}–{{ $fieldAgents->lastItem() ?? 0 }} of {{ $fieldAgents->total() }} field agents</span>
                    {{ $fieldAgents->links() }}
                </footer>
            </section>
        </section>
    </main>
@endsection
