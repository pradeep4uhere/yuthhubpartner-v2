@extends('layouts.app')
@section('content') <main class="admin-partners-page">
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

        {{-- Heading --}}
        <div class="admin-partners-heading">

            <div>
                <p class="admin-auth-eyebrow">
                    EVENT MANAGEMENT
                </p>

                <h1>Event Masters</h1>

                <p class="admin-users-intro">
                    Manage event types, categories, eligibility, fees and required documents.
                </p>
            </div>

            @can('event_masters.create')
                <a
                    class="admin-partners-primary"
                    href="{{ route('admin.event-masters.create') }}"
                >
                    Add event
                </a>
            @endcan

        </div>


        {{-- Success Message --}}
        @if (session('status'))
            <div
                class="admin-users-success"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif


        <section class="admin-partners-panel">

            {{-- Filters --}}
            <form
                method="GET"
                action="{{ route('admin.event-masters.index') }}"
                class="admin-partners-filters"
            >

                {{-- Search --}}
                <label class="admin-partners-search">

                    <span class="visually-hidden">
                        Search events
                    </span>

                    <input
                        type="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search event name, description or eligibility"
                    >

                </label>


                {{-- Event Type --}}
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


                {{-- Category --}}
                <label>

                    <span class="visually-hidden">
                        Filter by category
                    </span>

                    <select name="event_category_id">

                        <option value="">
                            All categories
                        </option>

                        @foreach ($eventCategories as $category)

                            <option
                                value="{{ $category->id }}"
                                @selected(
                                    (string) ($filters['event_category_id'] ?? '') ===
                                    (string) $category->id
                                )
                            >
                                {{ $category->name }}
                            </option>

                        @endforeach

                    </select>

                </label>


                {{-- Status --}}
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


                {{-- Apply --}}
                <button type="submit">
                    Apply
                </button>


                {{-- Clear --}}
                @if (!empty(array_filter(
                    $filters ?? [],
                    fn ($value) => $value !== null && $value !== ''
                )))
                    <a
                        class="admin-partners-reset"
                        href="{{ route('admin.event-masters.index') }}"
                    >
                        Clear
                    </a>
                @endif

            </form>


            {{-- Table --}}
            <div class="admin-partners-table-wrap">

                <table class="admin-partners-table">

                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Event Type</th>
                            <th>Category</th>
                            <th>Eligibility</th>
                            <th>Fee / Transaction</th>
                            <th>Documents</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>


                    <tbody>

                        @forelse ($eventMasters as $eventMaster)

                            <tr>

                                {{-- Event --}}
                                <td>
                                    <strong>
                                        {{ $eventMaster->event_name }}
                                    </strong>

                                    @if ($eventMaster->description)
                                        <br>

                                        <small>
                                            {{ \Illuminate\Support\Str::limit($eventMaster->description, 80) }}
                                        </small>
                                    @endif
                                </td>


                                {{-- Event Type --}}
                                <td>
                                    {{ optional($eventMaster->eventMasterType)->name ?? '—' }}
                                </td>


                                {{-- Category --}}
                                <td>
                                    {{ optional($eventMaster->eventCategory)->name ?? '—' }}
                                </td>


                                {{-- Eligibility --}}
                                <td>
                                    {{ $eventMaster->eligibility ?: '—' }}
                                </td>


                                {{-- Fee --}}
                                <td>

                                    @if ($eventMaster->fees_per_transactions !== null)

                                        ₹{{ number_format(
                                            (float) $eventMaster->fees_per_transactions,
                                            2
                                        ) }}

                                    @else

                                        —

                                    @endif

                                </td>


                                {{-- Documents --}}
                                <td>

                                    @php
                                        $documents = collect([
                                            $eventMaster->document_name_1,
                                            $eventMaster->document_name_2,
                                            $eventMaster->document_name_3,
                                        ])->filter()->count();
                                    @endphp

                                    {{ $documents }}

                                </td>


                                {{-- Status --}}
                                <td>

                                    <span
                                        class="admin-partner-status admin-partner-status--{{ $eventMaster->status ? 'active' : 'inactive' }}"
                                    >
                                        {{ $eventMaster->status ? 'Active' : 'Inactive' }}
                                    </span>

                                </td>


                                {{-- Created --}}
                                <td>
                                    {{ optional($eventMaster->created_at)->format('d M Y') }}
                                </td>


                                {{-- Actions --}}
                                <td>

                                    <div class="admin-partner-actions">

                                        @can('event_masters.update')

                                            <a
                                                href="{{ route('admin.event-masters.edit', $eventMaster) }}"
                                            >
                                                Edit
                                            </a>

                                        @endcan


                                        @can('event_masters.delete')

                                            <form
                                                method="POST"
                                                action="{{ route('admin.event-masters.destroy', $eventMaster) }}"
                                                onsubmit="return confirm('Delete this event master?')"
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
                                    colspan="9"
                                >
                                    No event masters found.
                                    Add an event master to get started.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            <footer class="admin-partners-footer">

                <span>
                    Showing
                    {{ $eventMasters->firstItem() ?? 0 }}
                    –
                    {{ $eventMasters->lastItem() ?? 0 }}
                    of
                    {{ $eventMasters->total() }}
                    event masters
                </span>

                {{ $eventMasters->links() }}

            </footer>

        </section>

    </section>

</main>


@endsection
