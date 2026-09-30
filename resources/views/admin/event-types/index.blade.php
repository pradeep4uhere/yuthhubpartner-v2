@extends('layouts.app')

@section('content')
    <main class="admin-partners-page">

        <header class="admin-users-header">
            <a href="{{ route('admin.dashboard') }}">
                &larr; Dashboard
            </a>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </header>


        <section class="admin-partners-content">

            <div class="admin-partners-heading">
                <div>
                    <p class="admin-auth-eyebrow">EVENT MANAGEMENT</p>

                    <h1>Event types</h1>

                    <p class="admin-users-intro">
                        Manage event types used across event categories and event masters.
                    </p>
                </div>

                @can('event_types.create')
                    <a
                        class="admin-partners-primary"
                        href="{{ route('admin.event-types.create') }}"
                    >
                        Add event type
                    </a>
                @endcan
            </div>


            @if (session('status'))
                <div class="admin-users-success" role="status">
                    {{ session('status') }}
                </div>
            @endif


            <section class="admin-partners-panel">

                <form
                    method="GET"
                    action="{{ route('admin.event-types.index') }}"
                    class="admin-partners-filters"
                >

                    <label class="admin-partners-search">
                        <span class="visually-hidden">
                            Search event types
                        </span>

                        <input
                            type="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search event type"
                        >
                    </label>


                    <label>
                        <span class="visually-hidden">
                            Filter by status
                        </span>

                        <select name="status">
                            <option value="">
                                All statuses
                            </option>

                            <option
                                value="1"
                                @selected(($filters['status'] ?? '') === '1')
                            >
                                Active
                            </option>

                            <option
                                value="0"
                                @selected(($filters['status'] ?? '') === '0')
                            >
                                Inactive
                            </option>
                        </select>
                    </label>


                    <button type="submit">
                        Apply
                    </button>


                    @if (!empty(array_filter($filters, fn ($value) => $value !== null && $value !== '')))
                        <a
                            class="admin-partners-reset"
                            href="{{ route('admin.event-types.index') }}"
                        >
                            Clear
                        </a>
                    @endif

                </form>


                <div class="admin-partners-table-wrap">

                    <table class="admin-partners-table">

                        <thead>
                            <tr>
                                <th>Event Type</th>
                                <th>Categories</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>


                        <tbody>

                            @forelse ($eventTypes as $eventType)

                                <tr>

                                    <td>
                                        <strong>
                                            {{ $eventType->name }}
                                        </strong>
                                    </td>


                                    <td>
                                        {{ $eventType->categories_count ?? $eventType->categories()->count() }}
                                    </td>


                                    <td>
                                        <span
                                            class="admin-partner-status admin-partner-status--{{ $eventType->status ? 'active' : 'inactive' }}"
                                        >
                                            {{ $eventType->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>


                                    <td>

                                        <div class="admin-partner-actions">

                                            @can('event_types.update')
                                                <a
                                                    href="{{ route('admin.event-types.edit', $eventType) }}"
                                                >
                                                    Edit
                                                </a>
                                            @endcan


                                            @can('event_types.delete')
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.event-types.destroy', $eventType) }}"
                                                    onsubmit="return confirm('Delete this event type?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan

                                        </div>

                                    </td>

                                </tr>


                            @empty

                                <tr>
                                    <td
                                        class="admin-partners-empty"
                                        colspan="4"
                                    >
                                        No event types found.
                                        Add an event type to get started.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                <footer class="admin-partners-footer">

                    <span>
                        Showing
                        {{ $eventTypes->firstItem() ?? 0 }}
                        –
                        {{ $eventTypes->lastItem() ?? 0 }}
                        of
                        {{ $eventTypes->total() }}
                        event types
                    </span>

                    {{ $eventTypes->links() }}

                </footer>

            </section>

        </section>

    </main>
@endsection