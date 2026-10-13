@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-log-history.css') }}">

<main class="admin-exports-page admin-log-history-page">
    <header class="admin-exports-header">
        <div class="admin-exports-header-left">
            <a href="{{ route('admin.dashboard') }}" class="admin-back-link">
                <span>←</span> Dashboard
            </a>
        </div>
        <div class="admin-exports-header-actions">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="export-signout">Sign out</button>
            </form>
        </div>
    </header>

    <section class="admin-exports-content">
        <div class="admin-exports-title">
            <div>
                <p class="export-eyebrow">SYSTEM MONITORING</p>
                <h1>Log History</h1>
                <p>Review Laravel logs, learner imports, exports and failed queue jobs in one place.</p>
            </div>
        </div>

        @if(session('status'))
            <div class="export-alert export-alert-success" role="status">{{ session('status') }}</div>
        @endif

        <section class="export-kpi-grid admin-log-kpi-grid">
            <article class="export-kpi-card">
                <div class="export-kpi-icon">≡</div>
                <div class="export-kpi-content"><span>Loaded Entries</span><strong>{{ number_format($summary['total']) }}</strong><small>Recent entries from supported sources</small></div>
            </article>
            <article class="export-kpi-card export-kpi-card-warning">
                <div class="export-kpi-icon">!</div>
                <div class="export-kpi-content"><span>Errors</span><strong>{{ number_format($summary['errors']) }}</strong><small class="negative">Errors and failed operations</small></div>
            </article>
            <article class="export-kpi-card">
                <div class="export-kpi-icon">△</div>
                <div class="export-kpi-content"><span>Warnings</span><strong>{{ number_format($summary['warnings']) }}</strong><small>Laravel warning entries</small></div>
            </article>
            <article class="export-kpi-card">
                <div class="export-kpi-icon">◷</div>
                <div class="export-kpi-content"><span>Today</span><strong>{{ number_format($summary['today']) }}</strong><small>Entries dated today</small></div>
            </article>
        </section>

        <section class="export-table-panel">
           <div class="export-table-heading">
            <div>
                <span>APPLICATION ACTIVITY</span>
                <h2>Log Directory</h2>
            </div>

            <div class="admin-log-heading-actions">
                <span class="export-result-count">
                    {{ number_format($logs->total()) }} matching entries
                </span>

                <form method="POST"
                    action="{{ route('admin.logs.clear') }}"
                    onsubmit="return confirm('Are you sure you want to clear Laravel application log files? This action cannot be undone.');">
                    @csrf

                    <button type="submit" class="admin-log-clear-btn">
                        Clear Logs
                    </button>
                </form>
            </div>
        </div>
            <form method="GET" action="{{ route('admin.logs.index') }}" class="export-filters admin-log-filters">
                <div class="export-search">
                    <span>⌕</span>
                    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search message, exception, file or ID...">
                </div>

                <select name="level" aria-label="Log level">
                    <option value="">All Levels</option>
                    @foreach(['EMERGENCY','ALERT','CRITICAL','ERROR','WARNING','NOTICE','INFO','DEBUG'] as $level)
                        <option value="{{ $level }}" @selected($filters['level'] === $level)>{{ $level }}</option>
                    @endforeach
                </select>

                <select name="source" aria-label="Log source">
                    <option value="">All Sources</option>
                    @foreach($sources as $key => $label)
                        <option value="{{ $key }}" @selected($filters['source'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                <label class="admin-log-date">From <input type="date" name="date_from" value="{{ $filters['date_from'] }}"></label>
                <label class="admin-log-date">To <input type="date" name="date_to" value="{{ $filters['date_to'] }}"></label>

                <div class="export-filter-actions">
                    <button type="submit" class="export-btn export-btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.logs.index') }}" class="export-clear-btn">Clear</a>
                </div>
            </form>

            <div class="export-table-wrap">
                <table class="export-table admin-log-table">
                    <thead>
                        <tr><th>Level</th><th>Event / Message</th><th>Source</th><th>Date &amp; Time</th><th>Details</th></tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        @php
                            $levelClass = in_array($log['level'], ['EMERGENCY','ALERT','CRITICAL','ERROR'], true) ? 'error'
                                : ($log['level'] === 'WARNING' ? 'warning' : 'info');
                            $detailsId = 'log-details-' . $loop->index;
                        @endphp
                        <tr>
                            <td><span class="admin-log-level admin-log-level--{{ $levelClass }}">{{ $log['level'] }}</span></td>
                            <td class="admin-log-message-cell">
                                <strong>{{ $log['title'] }}</strong>
                                <p>{{ \Illuminate\Support\Str::limit($log['message'], 220) }}</p>
                                @if($log['context'] !== '')
                                    <small>{{ $log['context'] }}</small>
                                @endif
                            </td>
                            <td><span class="export-type-badge">{{ $log['source'] }}</span></td>
                            <td>
                                <div class="export-requested">
                                    <strong>{{ $log['created_at'] ? \Illuminate\Support\Carbon::parse($log['created_at'])->format('d M Y') : 'Unknown date' }}</strong>
                                    <small>{{ $log['created_at'] ? \Illuminate\Support\Carbon::parse($log['created_at'])->format('h:i:s A') : '' }}</small>
                                </div>
                            </td>
                            <td>
                                <details class="admin-log-details" id="{{ $detailsId }}">
                                    <summary>View details</summary>
                                    <pre>{{ $log['details'] }}</pre>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="export-empty"><div><strong>No logs found</strong><span>Try changing the filters or check whether log files and history tables contain records.</span></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="export-table-footer">
                <span>Showing <strong>{{ $logs->firstItem() ?? 0 }}</strong>–<strong>{{ $logs->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($logs->total()) }}</strong></span>
                <div>{{ $logs->onEachSide(1)->links() }}</div>
            </footer>
        </section>

        <p class="admin-log-note">For performance, this page reads a bounded recent section of log files and recent rows from supported history tables. It is a viewer, not a replacement for log retention or alerting.</p>
    </section>
</main>
@endsection
