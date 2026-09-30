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

                    <h1>Event categories</h1>

                    <p class="admin-users-intro">
                        Manage event categories and their event types.
                    </p>
                </div>

                @can('event_categories.create')
                    <a
                        class="admin-partners-primary"
                        href="{{ route('admin.event-categories.create') }}"
                    >
                        Add event category
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
                    action="{{ route('admin.event-categories.index') }}"
                    class="admin-partners-filters"
                >

                    <label class="admin-partners-search">
                        <span class="visually-hidden">
                            Search event categories
                        </span>

                        <input
                            type="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search category"
                        >
                    </label>


                    <label>
                        <span class="visually-hidden">
                            Filter by event type
                        </span>

                        <select name="event_master_type_id">
                            <option value="">
                                All event types
                            </option>

                            @foreach ($eventMasterTypes as $type)
                                <option
                                    value="{{ $type->id }}"
                                    @selected(
                                        (string) ($filters['event_master_type_id'] ?? '') ===
                                        (string) $type->id
                                    )
                                >
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
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
                            href="{{ route('admin.event-categories.index') }}"
                        >
                            Clear
                        </a>
                    @endif

                </form>


                <div class="admin-partners-table-wrap">

                    <table class="admin-partners-table">

                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Event Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>


                        <tbody>

                            @forelse ($eventCategories as $eventCategory)

                                <tr>

                                    <td>
                                        <strong>
                                            {{ $eventCategory->name }}
                                        </strong>
                                    </td>


                                    <td>
                                        {{ optional($eventCategory->eventMasterType)->name ?? '—' }}
                                    </td>


                                    <td>
                                        <span
                                            class="admin-partner-status admin-partner-status--{{ $eventCategory->status ? 'active' : 'inactive' }}"
                                        >
                                            {{ $eventCategory->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>


                                    <td>

                                        <div class="admin-partner-actions">

                                            @can('event_categories.update')
                                                <a
                                                    href="{{ route('admin.event-categories.edit', $eventCategory) }}"
                                                >
                                                    Edit
                                                </a>
                                            @endcan


                                            @can('event_categories.delete')
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.event-categories.destroy', $eventCategory) }}"
                                                    onsubmit="return confirm('Delete this event category?')"
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
                                        No event categories found.
                                        Add an event category to get started.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                <footer class="admin-partners-footer">

                    <span>
                        Showing
                        {{ $eventCategories->firstItem() ?? 0 }}
                        –
                        {{ $eventCategories->lastItem() ?? 0 }}
                        of
                        {{ $eventCategories->total() }}
                        event categories
                    </span>

                    {{ $eventCategories->links() }}

                </footer>

            </section>

        </section>

    </main>
@endsection