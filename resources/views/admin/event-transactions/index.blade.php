@extends('layouts.app')

@section('content') <main class="admin-partners-page">

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
                <p class="admin-auth-eyebrow">EVENT MANAGEMENT</p>

                <h1>Event Transactions</h1>

                <p class="admin-users-intro">
                    Manage event transactions, review submissions and track workflow status.
                </p>
            </div>
           
        </div>

        @if (session('status'))
            <div class="admin-users-success" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="admin-users-error" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <section class="admin-partners-panel">

            {{-- Filters --}}
            <form method="GET"
                action="{{ route('admin.event-transactions.index') }}"
                class="admin-partners-filters">

                <label class="admin-partners-search">
                    <span class="visually-hidden">
                        Search event transactions
                    </span>

                    <input type="search"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search beneficiary, mobile, event">
                </label>

                <label>
                    <span class="visually-hidden">
                        Workflow Status
                    </span>

                    <select name="workflow_status">
                        <option value="">
                            All workflow statuses
                        </option>

                        @foreach ([
                            'draft' => 'Draft',
                            'submitted' => 'Submitted',
                            'under_review' => 'Under Review',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'resubmitted' => 'Resubmitted',
                        ] as $value => $label)

                            <option value="{{ $value }}"
                                @selected(($filters['workflow_status'] ?? '') === $value)>
                                {{ $label }}
                            </option>

                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="visually-hidden">
                        Review Status
                    </span>

                    <select name="review_status">
                        <option value="">
                            All review statuses
                        </option>

                        @foreach ([
                            'under_review' => 'Under Review',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'resubmitted' => 'Resubmitted',
                        ] as $value => $label)

                            <option value="{{ $value }}"
                                @selected(($filters['review_status'] ?? '') === $value)>
                                {{ $label }}
                            </option>

                        @endforeach
                    </select>
                </label>

                <button type="submit">
                    Apply
                </button>

                @if (!empty($filters))
                    <a class="admin-partners-reset"
                        href="{{ route('admin.event-transactions.index') }}">
                        Clear
                    </a>
                @endif

            </form>

            {{-- Table --}}
            <div class="admin-partners-table-wrap">

                <table class="admin-partners-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Beneficiary</th>
                            <th>Event</th>
                            <th>Partner</th>
                            <th>Field Agent</th>
                            <th>Event Date</th>
                            <th>Value</th>
                            <th>Workflow</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($transactions as $transaction)

                            <tr>

                                {{-- ID --}}
                                <td>
                                    <strong>
                                        #{{ $transaction->id }}
                                    </strong>
                                </td>

                                {{-- Beneficiary --}}
                                <td>
                                    <strong>
                                        {{ $transaction->beneficiary_name ?: '—' }}
                                    </strong>

                                    @if ($transaction->beneficiary_phone_number)
                                        <br>

                                        <small>
                                            {{ $transaction->beneficiary_phone_number }}
                                        </small>
                                    @endif
                                </td>

                                {{-- Event --}}
                                <td>
                                    <strong>
                                        {{ $transaction->event_type_name ?: '—' }}
                                    </strong>

                                    @if ($transaction->event_category_name)
                                        <br>

                                        <small>
                                            {{ $transaction->event_category_name }}
                                        </small>
                                    @endif
                                </td>

                                {{-- Partner --}}
                                <td>
                                    @if ($transaction->partner)
                                        <strong>
                                            {{ $transaction->partner->name }}
                                        </strong>

                                        @if ($transaction->partnerCenter)
                                            <br>

                                            <small>
                                                {{ $transaction->partnerCenter->name }}
                                            </small>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Field Agent --}}
                                <td>
                                    @if ($transaction->fieldAgent)

                                        {{ $transaction->fieldAgent->name }}

                                        <br>

                                        <small>
                                            {{ $transaction->fieldAgent->sakhi_id }}
                                        </small>

                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Event Date --}}
                                <td>
                                    {{ $transaction->event_create_date?->format('d M Y') ?: '—' }}
                                </td>

                                {{-- Event Value --}}
                                <td>
                                    @if ($transaction->event_value !== null)
                                        ₹{{ number_format((float) $transaction->event_value, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Workflow Status --}}
                                <td>

                                    @php
                                        $workflowStatus = $transaction->workflow_status;

                                        $statusClass = match ($workflowStatus) {
                                            'approved' => 'active',
                                            'rejected' => 'inactive',
                                            default => 'pending',
                                        };

                                        $statusLabel = match ($workflowStatus) {
                                            'under_review' => 'Under Review',
                                            'resubmitted' => 'Resubmitted',
                                            default => ucfirst($workflowStatus ?: 'Unknown'),
                                        };
                                    @endphp

                                    <span class="admin-partner-status admin-partner-status--{{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>

                                    @if ($transaction->review_status)
                                        <br>

                                        <small>
                                            Review:
                                            {{ ucfirst(str_replace('_', ' ', $transaction->review_status)) }}
                                        </small>
                                    @endif

                                </td>

                                {{-- Actions --}}
                                <td>

                                    <div class="admin-partner-actions">

                                        <a href="{{ route('admin.event-transactions.show', $transaction) }}">
                                            View
                                        </a>

                                        @can('update', $transaction)

                                            <a href="{{ route('admin.event-transactions.edit', $transaction) }}">
                                                Edit
                                            </a>

                                        @endcan

                                        @if (in_array($transaction->workflow_status, ['submitted', 'resubmitted']))

                                            @can('underReview', $transaction)

                                                <form method="POST"
                                                    action="{{ route('admin.event-transactions.under-review', $transaction) }}">

                                                    @csrf

                                                    <button type="submit">
                                                        Under Review
                                                    </button>

                                                </form>

                                            @endcan

                                        @endif

                                        @if (in_array($transaction->workflow_status, ['submitted', 'under_review', 'resubmitted']))

                                            @can('approve', $transaction)

                                                <form method="POST"
                                                    action="{{ route('admin.event-transactions.approve', $transaction) }}"
                                                    onsubmit="return confirm('Approve this event transaction?')">

                                                    @csrf

                                                    <button type="submit">
                                                        Approve
                                                    </button>

                                                </form>

                                            @endcan

                                            @can('reject', $transaction)

                                                <a href="{{ route('admin.event-transactions.show', $transaction) }}">
                                                    Reject
                                                </a>

                                            @endcan

                                        @endif

                                        @can('delete', $transaction)

                                            <form method="POST"
                                                action="{{ route('admin.event-transactions.destroy', $transaction) }}"
                                                onsubmit="return confirm('Delete this event transaction?')">

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
                                <td class="admin-partners-empty" colspan="9">
                                    No event transactions found.
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
                    {{ $transactions->firstItem() ?? 0 }}
                    –
                    {{ $transactions->lastItem() ?? 0 }}
                    of
                    {{ $transactions->total() }}
                    event transactions
                </span>

                {{ $transactions->links() }}

            </footer>

        </section>

    </section>

</main>


@endsection
