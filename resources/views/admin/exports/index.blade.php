@extends('layouts.app')

<link rel="stylesheet" href="{{ asset('css/admin-exports.css') }}">

@section('content')

<main class="admin-exports-page">


{{-- =========================================================
     HEADER
========================================================== --}}

<header class="admin-exports-header">

    <div class="admin-exports-header-left">

        <a href="{{ route('admin.dashboard') }}" class="admin-back-link">
            <span>←</span>
            Dashboard
        </a>

    </div>

    <div class="admin-exports-header-actions">

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <button type="submit" class="export-signout">
                Sign out
            </button>
        </form>

    </div>

</header>


{{-- =========================================================
     CONTENT
========================================================== --}}

<section class="admin-exports-content">

    {{-- TITLE --}}

    <div class="admin-exports-title">

        <div>

            <p class="export-eyebrow">
                EXPORT OPERATIONS
            </p>

            <h1>Downloads</h1>

            <p>
                Manage generated reports, export activity and downloadable files
                across the platform.
            </p>

        </div>

    </div>


    {{-- =====================================================
         ALERT
    ====================================================== --}}

    @if(session('status'))

        <div class="export-alert export-alert-success" role="status">

            <span>✓</span>

            {{ session('status') }}

        </div>

    @endif


    {{-- =====================================================
         KPI CARDS
    ====================================================== --}}

    <section class="export-kpi-grid">

        <article class="export-kpi-card">

            <div class="export-kpi-icon">
                ↓
            </div>

            <div class="export-kpi-content">

                <span>Total Exports</span>

                <strong>
                    {{ number_format($totalExports ?? 0) }}
                </strong>

                <small>
                    All export requests
                </small>

            </div>

        </article>


        <article class="export-kpi-card">

            <div class="export-kpi-icon">
                ✓
            </div>

            <div class="export-kpi-content">

                <span>Completed</span>

                <strong>
                    {{ number_format($completedExports ?? 0) }}
                </strong>

                <small class="positive">
                    Successfully generated
                </small>

            </div>

        </article>


        <article class="export-kpi-card">

            <div class="export-kpi-icon">
                ◌
            </div>

            <div class="export-kpi-content">

                <span>Processing</span>

                <strong>
                    {{ number_format($processingExports ?? 0) }}
                </strong>

                <small>
                    Currently in queue
                </small>

            </div>

        </article>


        <article class="export-kpi-card export-kpi-card-warning">

            <div class="export-kpi-icon">
                !
            </div>

            <div class="export-kpi-content">

                <span>Failed</span>

                <strong>
                    {{ number_format($failedExports ?? 0) }}
                </strong>

                <small class="negative">
                    Requires attention
                </small>

            </div>

        </article>

    </section>


    {{-- =====================================================
         SECONDARY STATS
    ====================================================== --}}

    <section class="export-secondary-stats">

        <div>

            <span>Today</span>

            <strong>
                {{ number_format($todayExports ?? 0) }}
            </strong>

        </div>

        <div>

            <span>This Week</span>

            <strong>
                {{ number_format($weeklyExports ?? 0) }}
            </strong>

        </div>

        <div>

            <span>Records Exported</span>

            <strong>
                {{ number_format($totalExportedRecords ?? 0) }}
            </strong>

        </div>

        <div>

            <span>Success Rate</span>

            <strong>
                {{ $successRate ?? 0 }}%
            </strong>

        </div>

    </section>


    {{-- =====================================================
         ANALYTICS
    ====================================================== --}}

    <section class="export-analytics-grid">

        {{-- EXPORT ACTIVITY --}}

        <div class="export-analytics-card">

            <div class="export-card-heading">

                <div>

                    <span>EXPORT ACTIVITY</span>

                    <h2>Last 7 Days</h2>

                </div>

                <div class="export-card-total">
                    {{ number_format($weeklyExports ?? 0) }}
                </div>

            </div>


            <div class="export-chart">

                @php
                    $maxExportTrend = max(
                        1,
                        collect($exportTrend ?? [])->max('total') ?? 1
                    );
                @endphp

                @forelse($exportTrend ?? [] as $item)

                    <div class="export-chart-column">

                        <div class="export-chart-value">
                            {{ $item['total'] ?? 0 }}
                        </div>

                        <div class="export-chart-track">

                            <div
                                class="export-chart-bar"
                                style="height: {{ max(5, (($item['total'] ?? 0) / $maxExportTrend) * 100) }}%;">
                            </div>

                        </div>

                        <span>
                            {{ $item['label'] ?? '' }}
                        </span>

                    </div>

                @empty

                    <div class="export-chart-empty">
                        No export activity available.
                    </div>

                @endforelse

            </div>

        </div>


        {{-- EXPORT TYPES --}}

        <div class="export-analytics-card export-type-card">

            <div class="export-card-heading">

                <div>

                    <span>EXPORT TYPES</span>

                    <h2>Distribution</h2>

                </div>

            </div>


            <div class="export-type-list">

                @php
                    $exportTypeLabels = [
                        'learners' => 'Learners',
                        'field_agents' => 'Field Agents',
                        'partners' => 'Partners',
                        'partner_centers' => 'Partner Centers',
                        'placement_partners' => 'Placement Partners',
                        'events' => 'Events',
                        'transactions' => 'Transactions',
                        'reports' => 'Reports',
                    ];
                @endphp


                @forelse($exportTypeStats ?? [] as $item)

                    @php
                        $type = $item['type'] ?? '';
                        $total = (int) ($item['total'] ?? 0);
                        $percentage = ($totalExports ?? 0) > 0
                            ? round(($total / $totalExports) * 100)
                            : 0;
                    @endphp

                    <div class="export-type-row">

                        <div class="export-type-info">

                            <strong>
                                {{ $exportTypeLabels[$type] ?? ucwords(str_replace('_', ' ', $type)) }}
                            </strong>

                            <span>
                                {{ number_format($total) }}
                            </span>

                        </div>

                        <div class="export-type-progress">

                            <div
                                style="width: {{ min(100, $percentage) }}%;">
                            </div>

                        </div>

                    </div>

                @empty

                    <div class="export-type-empty">
                        No export data available.
                    </div>

                @endforelse

            </div>

        </div>

    </section>


    {{-- =====================================================
         EXPORT DIRECTORY
    ====================================================== --}}

    <section class="export-table-panel">

        <div class="export-table-heading">

            <div>

                <span>DOWNLOAD CENTER</span>

                <h2>Export History</h2>

            </div>

            <span class="export-result-count">

                {{ number_format($exports->total()) }}

                matching exports

            </span>

        </div>


        {{-- FILTERS --}}

        <form
            method="GET"
            action="{{ route('admin.downloads.index') }}"
            class="export-filters">

            <div class="export-search">

                <span>⌕</span>

                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search export file or title...">

            </div>


            <select name="type">

                <option value="">
                    All Types
                </option>

                @foreach($exportTypeLabels as $key => $label)

                    <option
                        value="{{ $key }}"
                        @selected(($filters['type'] ?? '') === $key)>
                        {{ $label }}
                    </option>

                @endforeach

            </select>


            <select name="status">

                <option value="">
                    All Status
                </option>

                <option
                    value="completed"
                    @selected(($filters['status'] ?? '') === 'completed')>
                    Completed
                </option>

                <option
                    value="processing"
                    @selected(($filters['status'] ?? '') === 'processing')>
                    Processing
                </option>

                <option
                    value="queued"
                    @selected(($filters['status'] ?? '') === 'queued')>
                    Queued
                </option>

                <option
                    value="failed"
                    @selected(($filters['status'] ?? '') === 'failed')>
                    Failed
                </option>

            </select>


            <select name="scope_type">

                <option value="">
                    All Scopes
                </option>

                <option
                    value="system"
                    @selected(($filters['scope_type'] ?? '') === 'system')>
                    System
                </option>

                <option
                    value="placement_partner"
                    @selected(($filters['scope_type'] ?? '') === 'placement_partner')>
                    Placement Partner
                </option>

                <option
                    value="partner"
                    @selected(($filters['scope_type'] ?? '') === 'partner')>
                    Partner
                </option>

                <option
                    value="field_agent"
                    @selected(($filters['scope_type'] ?? '') === 'field_agent')>
                    Field Agent
                </option>

            </select>


            <div class="export-filter-actions">

                <button
                    type="submit"
                    class="export-btn export-btn-primary">

                    Apply Filters

                </button>


                <a
                    href="{{ route('admin.downloads.index') }}"
                    class="export-clear-btn">

                    Clear

                </a>

            </div>

        </form>


        {{-- TABLE --}}

        <div class="export-table-wrap">

            <table class="export-table">

                <thead>

                    <tr>

                        <th>Export</th>

                        <th>Type</th>

                        <th>Scope</th>

                        <th>Records</th>

                        <th>Requested</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($exports as $export)

                        <tr>

                            {{-- FILE --}}

                            <td>

                                <div class="export-file">

                                    <div class="export-file-icon">
                                        CSV
                                    </div>

                                    <div>

                                        <strong>
                                            {{ $export->title }}
                                        </strong>

                                        <small>
                                            {{ $export->file_name ?: 'File is being generated' }}
                                        </small>

                                    </div>

                                </div>

                            </td>


                            {{-- TYPE --}}

                            <td>

                                <span class="export-type-badge">

                                    {{ $exportTypeLabels[$export->type]
                                        ?? ucwords(str_replace('_', ' ', $export->type)) }}

                                </span>

                            </td>


                            {{-- SCOPE --}}

                            <td>

                                <div class="export-scope">

                                    <strong>
                                        {{ $export->scope_type
                                            ? ucwords(str_replace('_', ' ', $export->scope_type))
                                            : 'System' }}
                                    </strong>

                                    @if($export->scope_id)

                                        <small>
                                            ID: {{ $export->scope_id }}
                                        </small>

                                    @endif

                                </div>

                            </td>


                            {{-- RECORDS --}}

                            <td>

                                <strong class="export-record-count">

                                    {{ number_format($export->total_records ?? 0) }}

                                </strong>

                            </td>


                            {{-- REQUESTED --}}

                            <td>

                                <div class="export-requested">

                                    <strong>
                                        {{ optional($export->created_at)->format('d M Y') }}
                                    </strong>

                                    <small>
                                        {{ optional($export->created_at)->format('h:i A') }}
                                    </small>

                                </div>

                            </td>


                            {{-- STATUS --}}

                            <td>

                                <span
                                    class="export-status export-status--{{ $export->status }}">

                                    <span></span>

                                    {{ ucfirst($export->status) }}

                                </span>

                                @if($export->status === 'failed' && $export->error_message)

                                    <small class="export-error-preview">
                                        {{ \Illuminate\Support\Str::limit($export->error_message, 55) }}
                                    </small>

                                @endif

                            </td>


                            {{-- ACTION --}}

                            <td>

                                @if($export->status === 'completed' && $export->file_path)

                                    <a
                                        href="{{ route('admin.exports.download', $export) }}"
                                        class="export-download-btn">

                                        ↓ Download

                                    </a>

                                @elseif($export->status === 'processing')

                                    <span class="export-waiting">
                                        Processing…
                                    </span>

                                @elseif($export->status === 'queued')

                                    <span class="export-waiting">
                                        Queued…
                                    </span>

                                @else

                                    <span class="export-export-failed">
                                        —
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="export-empty">

                                <div>

                                    <strong>
                                        No exports found
                                    </strong>

                                    <span>
                                        Exported files will appear here once generated.
                                    </span>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- FOOTER --}}

        <footer class="export-table-footer">

            <span>

                Showing

                <strong>
                    {{ $exports->firstItem() ?? 0 }}
                </strong>

                –

                <strong>
                    {{ $exports->lastItem() ?? 0 }}
                </strong>

                of

                <strong>
                    {{ number_format($exports->total()) }}
                </strong>

            </span>


            <div>
                {{ $exports->onEachSide(1)->links() }}
            </div>

        </footer>

    </section>

</section>


</main>

@endsection
