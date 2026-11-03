@extends('layouts.app')

<link rel="stylesheet" href="{{ asset('css/admin-field-agents.css') }}">

@section('content')
<main class="admin-field-agents-page">
    <header class="admin-field-agents-header">
        <div class="admin-field-agents-header-left">
            <a href="{{ route('admin.dashboard') }}" class="admin-back-link">
                <span>←</span> Dashboard
            </a>
        </div>
        <div class="admin-field-agents-header-actions">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="field-agent-signout">Sign out</button>
            </form>
        </div>
    </header>

    <section class="admin-field-agents-content">
        <div class="admin-field-agents-title">
            <div>
                <p class="field-agent-eyebrow">EVENT MANAGEMENT</p>
                <h1>Event Transactions</h1>
                <p>Monitor event submissions, beneficiary activity and transaction workflow.</p>
            </div>
        </div>

        @if(session('status'))
            <div class="field-agent-alert field-agent-alert-success" role="status">
                <span>✓</span> {{ session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="field-agent-alert field-agent-alert-error" role="alert">
                <span>!</span> {{ session('error') }}
            </div>
        @endif

        @php
            $transactionTotal = $totalTransactions ?? ($transactions->total() ?? 0);
            $approvedTotal = $approvedTransactions ?? 0;
            $pendingTotal = $pendingReviewTransactions ?? 0;
            $rejectedTotal = $rejectedTransactions ?? 0;
            $submittedTotal = $submittedTransactions ?? 0;
            $underReviewTotal = $underReviewTransactions ?? 0;
            $resubmittedTotal = $resubmittedTransactions ?? 0;
            $todayTotal = $todayTransactions ?? 0;
            $trend = $transactionTrend ?? collect();
            $trendMax = max(1, (int) ($trend->max('total') ?? 0));
            $approvedPercent = $transactionTotal > 0 ? round(($approvedTotal / $transactionTotal) * 100, 1) : 0;
        @endphp

        <section class="field-agent-kpi-grid">
            <article class="field-agent-kpi-card">
                <div class="field-agent-kpi-icon">▤</div>
                <div class="field-agent-kpi-content">
                    <span>Total Transactions</span>
                    <strong>{{ number_format($transactionTotal) }}</strong>
                    <small>All matching records</small>
                </div>
            </article>
            <article class="field-agent-kpi-card">
                <div class="field-agent-kpi-icon">✓</div>
                <div class="field-agent-kpi-content">
                    <span>Approved</span>
                    <strong>{{ number_format($approvedTotal) }}</strong>
                    <small class="positive">Approved transactions</small>
                </div>
            </article>
            <article class="field-agent-kpi-card">
                <div class="field-agent-kpi-icon">◷</div>
                <div class="field-agent-kpi-content">
                    <span>Pending Review</span>
                    <strong>{{ number_format($pendingTotal) }}</strong>
                    <small>Awaiting review decision</small>
                </div>
            </article>
            <article class="field-agent-kpi-card field-agent-kpi-card-warning">
                <div class="field-agent-kpi-icon">!</div>
                <div class="field-agent-kpi-content">
                    <span>Rejected</span>
                    <strong>{{ number_format($rejectedTotal) }}</strong>
                    <small>Rejected submissions</small>
                </div>
            </article>
        </section>


        {{-- Workflow Status Summary --}}
        <section class="field-agent-secondary-stats">
            <div>
                <span>Total Transactions</span>
                <strong>{{ number_format($totalTransactions) }}</strong>
            </div>

            <div>
                <span>Draft</span>
                <strong>{{ number_format($draftTransactions) }}</strong>
            </div>

            <div>
                <span>Submitted</span>
                <strong>{{ number_format($submittedTransactions) }}</strong>
            </div>

            <div>
                <span>Under Review</span>
                <strong>{{ number_format($underReviewTransactions) }}</strong>
            </div>

            <div>
                <span>Approved</span>
                <strong>{{ number_format($approvedTransactions) }}</strong>
            </div>

            <div>
                <span>Rejected</span>
                <strong>{{ number_format($rejectedTransactions) }}</strong>
            </div>

            <div>
                <span>Resubmitted</span>
                <strong>{{ number_format($resubmittedTransactions) }}</strong>
            </div>
        </section>

        {{-- Review Status Summary --}}
        <section class="field-agent-secondary-stats">
            <div>
                <span>Open</span>
                <strong>{{ number_format($openReviewTransactions) }}</strong>
            </div>

            <div>
                <span>Accepted</span>
                <strong>{{ number_format($acceptedTransactions) }}</strong>
            </div>

            <div>
                <span>Pending</span>
                <strong>{{ number_format($pendingReviewTransactions) }}</strong>
            </div>

            <div>
                <span>Review Rejected</span>
                <strong>{{ number_format($rejectedReviewTransactions) }}</strong>
            </div>

            <div>
                <span>Returned</span>
                <strong>{{ number_format($returnedTransactions) }}</strong>
            </div>

            <div>
                <span>Created Today</span>
                <strong>{{ number_format($todayTransactions) }}</strong>
            </div>
        </section>


        <section class="field-agent-analytics-grid">
            <div class="field-agent-analytics-card">
                <div class="field-agent-card-heading">
                    <div><span>TRANSACTION ACTIVITY</span><h2>Last 7 Days</h2></div>
                    <div class="field-agent-card-total">{{ number_format($lastSevenDaysTransactions ?? $trend->sum('total')) }}</div>
                </div>
                <div class="field-agent-chart">
                    @forelse($trend as $item)
                        <div class="field-agent-chart-column">
                            <div class="field-agent-chart-value">{{ $item['total'] ?? 0 }}</div>
                            <div class="field-agent-chart-track">
                                <div class="field-agent-chart-bar" style="height: {{ max(5, (($item['total'] ?? 0) / $trendMax) * 100) }}%;"></div>
                            </div>
                            <span>{{ $item['label'] ?? '' }}</span>
                        </div>
                    @empty
                        <div class="field-agent-chart-empty">Transaction trend data is not available yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="field-agent-analytics-card field-agent-progress-card">
                <div class="field-agent-card-heading">
                    <div><span>WORKFLOW OVERVIEW</span><h2>Approved Transactions</h2></div>
                </div>
                <div class="field-agent-progress-circle" style="--completion: {{ $approvedPercent * 3.6 }}">
                    <div><strong>{{ $approvedPercent }}%</strong><span>Approved</span></div>
                </div>
                <div class="field-agent-progress-legend">
                    <span><i class="assigned"></i> Approved <b>{{ number_format($approvedTotal) }}</b></span>
                    <span><i class="unassigned"></i> Not approved <b>{{ number_format(max(0, $transactionTotal - $approvedTotal)) }}</b></span>
                </div>
            </div>
        </section>

        <section class="field-agent-table-panel mt-5">
            <div class="field-agent-table-heading">
                <div><span>TRANSACTION DIRECTORY</span><h2>All Event Transactions</h2></div>
                <span class="field-agent-result-count">{{ number_format($transactions->total()) }} matching records</span>
            </div>

            <form method="GET" action="{{ route('admin.event-transactions.index') }}" class="field-agent-filters">
                <div class="field-agent-search">
                    <span>⌕</span>
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Search beneficiary, mobile, event...">
                </div>

                @php
                    use App\Support\EventTransactionStatus;
                @endphp
                <select name="workflow_status" aria-label="Workflow status">
                    <option value="">All Workflow Statuses</option>

                    @foreach(EventTransactionStatus::options() as $value => $label)
                        <option value="{{ $value }}"
                            @selected(($filters['workflow_status'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                 @use('App\Support\EventTransactionReviewStatus')

                <select name="review_status">
                    <option value="">All Review Statuses</option>

                    @foreach(EventTransactionReviewStatus::options() as $value => $label)
                        <option value="{{ $value }}"
                            @selected(request('review_status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <div class="field-agent-filter-actions">
                    <button type="submit" class="field-agent-btn field-agent-btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.event-transactions.index') }}" class="field-agent-clear-btn">Clear</a>
                </div>
            </form>

            <div class="field-agent-table-wrap">
                <table class="field-agent-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Beneficiary</th><th>Event</th><th>Partner / Center</th>
                            <th>Field Agent</th><th>Event Date</th><th>Value</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($transactions as $transaction)
                       @php
                            $reviewStatus = strtolower(trim($transaction->review_status ?? ''));

                            $reviewClass = match ($reviewStatus) {
                                'approved', 'accepted' => 'field-status-approved',
                                'rejected'             => 'field-status-rejected',
                                'returned', 'return'   => 'field-status-returned',
                                'open', 'pending',
                                'under_review'         => 'field-status-under-review',
                                default                => 'field-status-default',
                            };

                            $reviewLabel = ucfirst(str_replace('_', ' ', $reviewStatus));
                        @endphp
                        <tr>
                            <td><strong>#{{ $transaction->id }}</strong></td>
                            <td>
                                <div class="field-agent-person">
                                    <div class="field-agent-avatar">{{ strtoupper(substr(trim($transaction->beneficiary_name ?? 'B'), 0, 1)) }}</div>
                                    <div>
                                        <strong>{{ $transaction->beneficiary_name ?: '—' }}</strong>
                                        <small>{{ $transaction->beneficiary_phone_number ?: 'Mobile not available' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="field-agent-partner">
                                    <strong>{{ $transaction->event_type_name ?: '—' }}</strong>
                                    @if($transaction->event_category_name)<small>{{ $transaction->event_category_name }}</small>@endif
                                </div>
                            </td>
                            <td>
                                @if($transaction->partner)
                                    <div class="field-agent-partner">
                                        <strong>{{ $transaction->partner->name }}</strong>
                                        @if($transaction->partnerCenter)<small>{{ $transaction->partnerCenter->name }}</small>@endif
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($transaction->fieldAgent)
                                    <div class="field-agent-person">
                                        <div class="field-agent-avatar">{{ strtoupper(substr(trim($transaction->fieldAgent->name ?? 'F'), 0, 1)) }}</div>
                                        <div><strong>{{ $transaction->fieldAgent->name }}</strong><small>{{ $transaction->fieldAgent->sakhi_id ?: 'Sakhi ID unavailable' }}</small></div>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $transaction->event_create_date?->format('d M Y') ?: '—' }}</td>
                            <td>
                                @if($transaction->event_value !== null)
                                    ₹{{ number_format((float) $transaction->event_value, 2) }}
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                {{-- Review Status --}}
                                @if($transaction->review_status)
                                    @php
                                        $reviewStatus = strtolower($transaction->review_status);

                                        $reviewClass = match($reviewStatus) {
                                            'approved', 'accepted' => 'field-status-approved',
                                            'rejected'             => 'field-status-rejected',
                                            'returned', 'return'   => 'field-status-returned',
                                            'under_review', 'pending' => 'field-status-under-review',
                                            default                => 'field-status-default',
                                        };

                                        $reviewLabel = ucfirst(str_replace('_', ' ', $reviewStatus));
                                    @endphp

                                    <span class="field-status-badge {{ $reviewClass }}">
                                        <span class="status-dot"></span>
                                        Review: {{ $reviewLabel }}
                                    </span>
                                @endif

                                {{-- Workflow Status --}}
                                @php
                                    $workflowStatus = strtolower($transaction->workflow_status ?? '');

                                    $workflowClass = match($workflowStatus) {
                                        'approved'                 => 'field-status-approved',
                                        'rejected'                 => 'field-status-rejected',
                                        'returned', 'return'       => 'field-status-returned',
                                        'under_review'             => 'field-status-under-review',
                                        'submitted', 'resubmitted' => 'field-status-submitted',
                                        'draft'                    => 'field-status-draft',
                                        default                    => 'field-status-default',
                                    };

                                    $workflowLabel = ucfirst(str_replace('_', ' ', $workflowStatus ?: 'Unknown'));
                                @endphp

                                <span class="field-status-badge {{ $workflowClass }}">
                                    <span class="status-dot"></span>
                                    Workflow: {{ $workflowLabel }}
                                </span>
                            </td>

                            <td>
                                <div class="field-agent-actions">
                                    <a href="{{ route('admin.event-transactions.show', $transaction) }}">View</a>
                                    @can('update', $transaction)
                                        <a href="{{ route('admin.event-transactions.edit', $transaction) }}">Edit</a>
                                    @endcan

                                    @can('delete', $transaction)
                                        <form method="POST" action="{{ route('admin.event-transactions.destroy', $transaction) }}"
                                              onsubmit="return confirm('Delete this event transaction?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="field-agent-empty">
                            <div><strong>No event transactions found</strong><span>Try changing your search or filters.</span></div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="field-agent-table-footer">
                <span>Showing <strong>{{ $transactions->firstItem() ?? 0 }}</strong> – <strong>{{ $transactions->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($transactions->total()) }}</strong> event transactions</span>
                <div>{{ $transactions->onEachSide(1)->links() }}</div>
            </footer>
        </section>
    </section>
</main>
@endsection
