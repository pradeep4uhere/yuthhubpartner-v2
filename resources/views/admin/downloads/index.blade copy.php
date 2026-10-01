@extends('layouts.app')
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-downloads.css') }}">
@endsection
@section('content') <main class="admin-downloads-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <header class="downloads-page-header">

        <div class="downloads-page-header__content">

            <div class="downloads-breadcrumb">
                <span>ADMIN</span>
                <i></i>
                <span>OPERATIONS</span>
                <i></i>
                <strong>DOWNLOADS</strong>
            </div>

            <div class="downloads-title-row">

                <div class="downloads-title-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </div>

                <div>
                    <h1>Downloads</h1>
                    <p>
                        Export Center
                        <span>·</span>
                        Manage generated reports and data exports
                    </p>
                </div>

            </div>

        </div>

        <div class="downloads-live-indicator">
            <span class="downloads-live-indicator__dot"></span>
            <span>Live status</span>
        </div>

    </header>


    {{-- =========================================================
         SUMMARY CARDS
    ========================================================== --}}
    @php
        $totalExports = $exports->total();

        $processingCount = \App\Models\LearnerExport::query()
            ->where('admin_id', auth('admin')->id())
            ->whereIn('status', ['queued', 'processing'])
            ->count();

        $readyCount = \App\Models\LearnerExport::query()
            ->where('admin_id', auth('admin')->id())
            ->where('status', 'completed')
            ->count();

        $failedCount = \App\Models\LearnerExport::query()
            ->where('admin_id', auth('admin')->id())
            ->where('status', 'failed')
            ->count();
    @endphp

    <section class="downloads-summary">

        <article class="download-stat-card">

            <div class="download-stat-card__top">
                <span class="download-stat-card__label">
                    Total Exports
                </span>

                <span class="download-stat-card__icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <path d="M14 2v6h6"/>
                        <path d="M8 13h8"/>
                        <path d="M8 17h6"/>
                    </svg>
                </span>
            </div>

            <strong class="download-stat-card__value">
                {{ number_format($totalExports) }}
            </strong>

            <span class="download-stat-card__hint">
                All generated exports
            </span>

        </article>


        <article class="download-stat-card download-stat-card--processing">

            <div class="download-stat-card__top">
                <span class="download-stat-card__label">
                    Processing
                </span>

                <span class="download-stat-card__icon">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <polyline points="12 7 12 12 15 14"/>
                    </svg>
                </span>
            </div>

            <strong
                class="download-stat-card__value"
                data-processing-count
            >
                {{ number_format($processingCount) }}
            </strong>

            <span class="download-stat-card__hint">
                Being prepared
            </span>

        </article>


        <article class="download-stat-card download-stat-card--ready">

            <div class="download-stat-card__top">
                <span class="download-stat-card__label">
                     Ready
                </span>

                <span class="download-stat-card__icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </span>
            </div>

            <strong class="download-stat-card__value">
                {{ number_format($readyCount) }}
            </strong>

            <span class="download-stat-card__hint">
                Available to download
            </span>

        </article>


        <article class="download-stat-card download-stat-card--failed">

            <div class="download-stat-card__top">
                <span class="download-stat-card__label">
                    Failed
                </span>

                <span class="download-stat-card__icon">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </span>
            </div>

            <strong class="download-stat-card__value">
                {{ number_format($failedCount) }}
            </strong>

            <span class="download-stat-card__hint">
                Requires attention
            </span>

        </article>

    </section>


    {{-- =========================================================
         EXPORT TABLE CARD
    ========================================================== --}}
    <section class="downloads-panel">

        <div class="downloads-panel__header">

            <div>
                <h2>Export History</h2>
                <p>
                    Track, monitor and download generated files.
                </p>
            </div>

            <div class="downloads-panel__tools">

                <div class="downloads-refresh">
                    <span class="downloads-refresh__dot"></span>
                    Auto refresh
                </div>

            </div>

        </div>


        {{-- Filters --}}
        <div class="downloads-filters">

            <div class="downloads-search">

                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-4-4"/>
                </svg>

                <input
                    type="search"
                    id="downloadSearch"
                    placeholder="Search exports..."
                    autocomplete="off"
                >

            </div>


            <div class="downloads-filter-group">

                <button
                    type="button"
                    class="download-filter is-active"
                    data-filter="all"
                >
                    All
                    <span>{{ $totalExports }}</span>
                </button>

                <button
                    type="button"
                    class="download-filter"
                    data-filter="processing"
                >
                    Processing
                    <span>{{ $processingCount }}</span>
                </button>

                <button
                    type="button"
                    class="download-filter"
                    data-filter="completed"
                >
                    Ready
                    <span>{{ $readyCount }}</span>
                </button>

                <button
                    type="button"
                    class="download-filter"
                    data-filter="failed"
                >
                    Failed
                    <span>{{ $failedCount }}</span>
                </button>

            </div>

        </div>


        @if($exports->count())

            <div class="downloads-table-container">

                <table class="downloads-table">

                    <thead>
                        <tr>
                            <th class="downloads-col-file">EXPORT</th>
                            <th>RECORDS</th>
                            <th>STATUS</th>
                            <th>CREATED</th>
                            <th>COMPLETED</th>
                            <th class="downloads-col-action">ACTION</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($exports as $export)

                            @php
                                $status = $export->status ?? 'queued';

                                $statusLabel = match ($status) {
                                    'completed' => 'Ready',
                                    'processing' => 'Processing',
                                    'failed' => 'Failed',
                                    default => 'Queued',
                                };

                                $extension = strtolower(
                                    pathinfo(
                                        $export->file_name ?? 'export.csv',
                                        PATHINFO_EXTENSION
                                    )
                                );

                                $fileType = strtoupper(
                                    $extension ?: 'CSV'
                                );

                                $searchText = strtolower(
                                    ($export->file_name ?? '') .
                                    ' ' .
                                    ($export->title ?? '') .
                                    ' ' .
                                    $export->id
                                );
                            @endphp

                            <tr
                                class="download-row"
                                data-export-row="{{ $export->id }}"
                                data-status-url="{{ route('admin.downloads.status', $export) }}"
                                data-export-status="{{ $status }}"
                                data-search="{{ $searchText }}"
                            >

                                {{-- Export --}}
                                <td>

                                    <div class="download-export">

                                        <div class="download-export__file-icon">

                                            @if(in_array($extension, ['xlsx', 'xls']))

                                                <svg viewBox="0 0 24 24">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <path d="M14 2v6h6"/>
                                                    <path d="m8 13 2 2-2 2"/>
                                                    <path d="m16 13-2 2 2 2"/>
                                                </svg>

                                            @elseif($extension === 'pdf')

                                                <svg viewBox="0 0 24 24">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <path d="M14 2v6h6"/>
                                                    <path d="M8 15h8"/>
                                                    <path d="M8 18h5"/>
                                                </svg>

                                            @else

                                                <svg viewBox="0 0 24 24">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <path d="M14 2v6h6"/>
                                                    <path d="M8 13h8"/>
                                                    <path d="M8 17h8"/>
                                                </svg>

                                            @endif

                                            <span>
                                                {{ $fileType }}
                                            </span>

                                        </div>


                                        <div class="download-export__info">

                                            <strong class="export-file-name">
                                                {{ $export->file_name ?: ($export->title ?? 'Learner Export') }}
                                            </strong>

                                            <div class="download-export__meta">
                                                <span>
                                                    {{ $export->title ?? 'Export' }}
                                                    #{{ $export->id }}
                                                </span>

                                                <i></i>

                                                <span>
                                                    {{ $fileType }} file
                                                </span>
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- Records --}}
                                <td>

                                    <div class="download-records">

                                        <strong class="export-records">
                                            {{ number_format($export->total_records ?? 0) }}
                                        </strong>

                                        <span>records</span>

                                    </div>

                                </td>


                                {{-- Status --}}
                                <td>

                                    <div class="download-status download-status--{{ $status }}">

                                        <span class="download-status__indicator">
                                            @if($status === 'completed')
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M20 6 9 17l-5-5"/>
                                                </svg>
                                            @elseif($status === 'failed')
                                                <svg viewBox="0 0 24 24">
                                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                                </svg>
                                            @else
                                                <span class="status-spinner"></span>
                                            @endif
                                        </span>

                                        <span class="export-status">
                                            {{ $statusLabel }}
                                        </span>

                                    </div>

                                    @if($status === 'failed' && $export->error_message)

                                        <div class="download-error">
                                            {{ \Illuminate\Support\Str::limit($export->error_message, 70) }}
                                        </div>

                                    @endif

                                </td>


                                {{-- Created --}}
                                <td>

                                    <div class="download-time">
                                        <strong>
                                            {{ optional($export->created_at)->format('d M Y') }}
                                        </strong>

                                        <span>
                                            {{ optional($export->created_at)->format('h:i A') }}
                                        </span>
                                    </div>

                                </td>


                                {{-- Completed --}}
                                <td>

                                    <div class="download-time export-completed-at">

                                        @if($export->completed_at)

                                            <strong>
                                                {{ $export->completed_at->format('d M Y') }}
                                            </strong>

                                            <span>
                                                {{ $export->completed_at->format('h:i A') }}
                                            </span>

                                        @else

                                            <span class="download-time__empty">
                                                —
                                            </span>

                                        @endif

                                    </div>

                                </td>


                                {{-- Action --}}
                                <td class="downloads-col-action">

                                    <div class="export-action">

                                        @if($status === 'completed')

                                            <a
                                                href="{{ route('admin.downloads.download', $export) }}"
                                                class="download-action"
                                            >
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M12 3v12"/>
                                                    <path d="m7 10 5 5 5-5"/>
                                                    <path d="M5 21h14"/>
                                                </svg>

                                                <span>Download</span>
                                            </a>

                                        @elseif($status === 'failed')

                                            <span class="download-action download-action--failed">
                                                Failed
                                            </span>

                                        @else

                                            <span class="download-action download-action--processing">
                                                <span class="preparing-spinner"></span>
                                                Preparing
                                            </span>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Mobile list --}}
            <div class="downloads-mobile-list">

                @foreach($exports as $export)

                    @php
                        $status = $export->status ?? 'queued';

                        $statusLabel = match ($status) {
                            'completed' => 'Ready',
                            'processing' => 'Processing',
                            'failed' => 'Failed',
                            default => 'Queued',
                        };
                    @endphp

                    <article
                        class="download-mobile-card"
                        data-mobile-export="{{ $export->id }}"
                    >

                        <div class="download-mobile-card__top">

                            <div class="download-mobile-card__file">

                                <span class="mobile-file-badge">
                                    CSV
                                </span>

                                <div>
                                    <strong class="mobile-file-name">
                                        {{ $export->file_name ?: 'Learner Export' }}
                                    </strong>

                                    <small>
                                        Export #{{ $export->id }}
                                    </small>
                                </div>

                            </div>

                            <span class="mobile-status mobile-status--{{ $status }}">
                                {{ $statusLabel }}
                            </span>

                        </div>


                        <div class="download-mobile-card__details">

                            <div>
                                <span>Records</span>
                                <strong class="mobile-records">
                                    {{ number_format($export->total_records ?? 0) }}
                                </strong>
                            </div>

                            <div>
                                <span>Created</span>
                                <strong>
                                    {{ optional($export->created_at)->format('d M Y, h:i A') }}
                                </strong>
                            </div>

                        </div>


                        @if($status === 'completed')

                            <a
                                href="{{ route('admin.downloads.download', $export) }}"
                                class="mobile-download-btn"
                            >
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 3v12"/>
                                    <path d="m7 10 5 5 5-5"/>
                                    <path d="M5 21h14"/>
                                </svg>
                                Download File
                            </a>

                        @else

                            <div class="mobile-download-btn mobile-download-btn--disabled">
                                <span class="preparing-spinner"></span>
                                {{ $status === 'failed' ? 'Export Failed' : 'Preparing File...' }}
                            </div>

                        @endif

                    </article>

                @endforeach

            </div>


            {{-- Pagination --}}
            @if($exports->hasPages())

                <div class="downloads-pagination">

                    <div class="downloads-pagination__summary">
                        Showing
                        <strong>{{ $exports->firstItem() }}</strong>
                        –
                        <strong>{{ $exports->lastItem() }}</strong>
                        of
                        <strong>{{ $exports->total() }}</strong>
                    </div>

                    <div class="downloads-pagination__links">
                        {{ $exports->links() }}
                    </div>

                </div>

            @endif

        @else

            <div class="downloads-empty">

                <div class="downloads-empty__visual">

                    <div class="downloads-empty__circle">
                        <svg viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                    </div>

                </div>

                <h3>No exports yet</h3>

                <p>
                    Generated reports and data exports will appear here.
                </p>

                <span>
                    Start an export from Learners, Events, Field Agents or Reports.
                </span>

            </div>

        @endif

    </section>

</main>


{{-- =========================================================
     SEARCH + FILTER + LIVE STATUS
========================================================== --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {

        const rows = [...document.querySelectorAll('.download-row')];
        const filters = [...document.querySelectorAll('.download-filter')];
        const searchInput = document.getElementById('downloadSearch');

        let activeFilter = 'all';


        /* -----------------------------------------------------
           FILTER
        ----------------------------------------------------- */

        const applyFilters = () => {

            const search = (searchInput?.value || '')
                .trim()
                .toLowerCase();

            rows.forEach(row => {

                const status = row.dataset.exportStatus || '';
                const text = row.dataset.search || '';

                const matchesStatus =
                    activeFilter === 'all' ||
                    status === activeFilter;

                const matchesSearch =
                    !search ||
                    text.includes(search);

                row.style.display =
                    matchesStatus && matchesSearch
                        ? ''
                        : 'none';
            });
        };


        filters.forEach(button => {

            button.addEventListener('click', () => {

                filters.forEach(item => {
                    item.classList.remove('is-active');
                });

                button.classList.add('is-active');

                activeFilter =
                    button.dataset.filter || 'all';

                applyFilters();

            });

        });


        searchInput?.addEventListener('input', applyFilters);


        /* -----------------------------------------------------
           LIVE STATUS
        ----------------------------------------------------- */

        const updateRow = async (row) => {

            const url = row.dataset.statusUrl;

            if (!url || row.dataset.finished === '1') {
                return;
            }

            try {

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                const status = data.status || 'queued';

                row.dataset.exportStatus = status;


                /* Records */
                const records =
                    row.querySelector('.export-records');

                if (records) {

                    records.textContent =
                        Number(data.total_records || 0)
                            .toLocaleString('en-IN');

                }


                /* Filename */
                const filename =
                    row.querySelector('.export-file-name');

                if (filename && data.file_name) {
                    filename.textContent = data.file_name;
                }


                /* Status */
                const statusText =
                    row.querySelector('.export-status');

                if (statusText) {

                    const labels = {
                        queued: 'Queued',
                        processing: 'Processing',
                        completed: 'Ready',
                        failed: 'Failed'
                    };

                    statusText.textContent =
                        labels[status] || status;

                }


                /* Status wrapper */
                const statusWrapper =
                    row.querySelector('.download-status');

                if (statusWrapper) {

                    statusWrapper.className =
                        `download-status download-status--${status}`;

                }


                /* Completed date */
                const completed =
                    row.querySelector('.export-completed-at');

                if (completed && data.completed_at) {

                    completed.innerHTML = `
                        <strong>
                            ${data.completed_at.split(',')[0]}
                        </strong>
                        <span>
                            ${data.completed_at.split(',').slice(1).join(',').trim()}
                        </span>
                    `;

                }


                /* Action */
                const action =
                    row.querySelector('.export-action');

                if (action && status === 'completed') {

                    action.innerHTML = `
                        <a
                            href="${data.download_url}"
                            class="download-action"
                        >
                            <svg viewBox="0 0 24 24">
                                <path d="M12 3v12"/>
                                <path d="m7 10 5 5 5-5"/>
                                <path d="M5 21h14"/>
                            </svg>
                            <span>Download</span>
                        </a>
                    `;

                    row.dataset.finished = '1';

                }


                if (action && status === 'failed') {

                    action.innerHTML = `
                        <span class="download-action download-action--failed">
                            Failed
                        </span>
                    `;

                    row.dataset.finished = '1';

                }


                applyFilters();

            } catch (error) {

                console.error(
                    'Export status update failed:',
                    error
                );

            }

        };


        const poll = () => {

            rows.forEach(row => {
                updateRow(row);
            });

        };


        if (rows.length) {

            poll();

            setInterval(poll, 5000);

        }

    });
</script>


@endsection

@section('content') <main class="admin-downloads-page">
    {{-- Page Header --}}
    <section class="downloads-header">
        <div>
            <div class="downloads-eyebrow">
                <span class="downloads-eyebrow__dot"></span>
                ADMINISTRATION
            </div>

            <h1>Downloads</h1>

            <p>
                View and download your generated reports and data exports.
            </p>
        </div>

        <div class="downloads-header__meta">
            <span class="downloads-header__count">
                {{ $exports->total() }}
            </span>
            <span>Total Exports</span>
        </div>
    </section>


    {{-- Downloads Card --}}
    <section class="downloads-card">

        <div class="downloads-card__header">
            <div>
                <h2>Export History</h2>
                <p>
                    Files generated from the administrator workspace.
                </p>
            </div>

            <div class="downloads-card__status">
                <span class="status-dot"></span>
                Auto-updating
            </div>
        </div>


        @if($exports->count())

            <div class="downloads-table-wrapper">

                <table class="downloads-table">

                    <thead>
                        <tr>
                            <th>TYPE</th>
                            <th>FILE / EXPORT</th>
                            <th>RECORDS</th>
                            <th>STATUS</th>
                            <th>CREATED</th>
                            <th>COMPLETED</th>
                            <th class="text-right">ACTION</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($exports as $export)

                            <tr
                                data-export-row="{{ $export->id }}"
                                data-status-url="{{ route('admin.downloads.status', $export) }}"
                            >

                                {{-- File Type --}}
                                <td>
                                    <span class="download-file-type">
                                        @php
                                            $extension = strtolower(
                                                pathinfo($export->file_name ?? 'export.csv', PATHINFO_EXTENSION)
                                            );
                                        @endphp

                                        {{ strtoupper($extension ?: 'CSV') }}
                                    </span>
                                </td>


                                {{-- File --}}
                                <td>
                                    <div class="download-file">

                                        <div class="download-file__icon">
                                            @if($extension === 'xlsx' || $extension === 'xls')
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <path d="M14 2v6h6"/>
                                                    <path d="m8 13 2 2-2 2"/>
                                                    <path d="m16 13-2 2 2 2"/>
                                                </svg>
                                            @elseif($extension === 'pdf')
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <path d="M14 2v6h6"/>
                                                    <path d="M8 15h8"/>
                                                    <path d="M8 18h5"/>
                                                </svg>
                                            @else
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <path d="M14 2v6h6"/>
                                                    <path d="M8 13h8"/>
                                                    <path d="M8 17h8"/>
                                                </svg>
                                            @endif
                                        </div>

                                        <div class="download-file__details">

                                            <strong class="export-file-name">
                                                {{ $export->file_name ?: ($export->title ?? 'Learner Export') }}
                                            </strong>

                                            <small>
                                                {{ $export->title ?? 'Export' }}
                                                #{{ $export->id }}
                                            </small>

                                        </div>

                                    </div>
                                </td>


                                {{-- Records --}}
                                <td>
                                    <span class="export-records">
                                        {{ number_format($export->total_records ?? 0) }}
                                    </span>
                                </td>


                                {{-- Status --}}
                                <td>
                                    @php
                                        $status = $export->status ?? 'queued';

                                        $statusLabel = match ($status) {
                                            'completed' => 'Ready',
                                            'processing' => 'Processing',
                                            'failed' => 'Failed',
                                            default => 'Queued',
                                        };
                                    @endphp

                                    <span class="download-status download-status--{{ $status }}">
                                        <span class="download-status__dot"></span>
                                        <span class="export-status">
                                            {{ $statusLabel }}
                                        </span>
                                    </span>

                                    @if($status === 'failed' && $export->error_message)
                                        <small class="download-error">
                                            {{ $export->error_message }}
                                        </small>
                                    @endif
                                </td>


                                {{-- Created --}}
                                <td>
                                    <span class="download-date">
                                        {{ optional($export->created_at)->format('d M Y, h:i A') }}
                                    </span>
                                </td>


                                {{-- Completed --}}
                                <td>
                                    <span class="export-completed-at">
                                        @if($export->completed_at)
                                            {{ $export->completed_at->format('d M Y, h:i A') }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </td>


                                {{-- Action --}}
                                <td class="text-right">

                                    <div class="export-action">

                                        @if($status === 'completed')

                                            <a
                                                href="{{ route('admin.downloads.download', $export) }}"
                                                class="download-btn"
                                            >
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M12 3v12"/>
                                                    <path d="m7 10 5 5 5-5"/>
                                                    <path d="M5 21h14"/>
                                                </svg>

                                                <span>Download</span>
                                            </a>

                                        @elseif($status === 'failed')

                                            <span class="download-failed">
                                                Export failed
                                            </span>

                                        @else

                                            <span class="export-preparing">
                                                <span class="preparing-spinner"></span>
                                                Preparing...
                                            </span>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            @if($exports->hasPages())

                <div class="downloads-pagination">

                    <div class="downloads-pagination__info">
                        Showing
                        <strong>{{ $exports->firstItem() }}</strong>
                        to
                        <strong>{{ $exports->lastItem() }}</strong>
                        of
                        <strong>{{ $exports->total() }}</strong>
                        exports
                    </div>

                    <div class="downloads-pagination__links">
                        {{ $exports->links() }}
                    </div>

                </div>

            @endif

        @else

            {{-- Empty State --}}
            <div class="downloads-empty">

                <div class="downloads-empty__icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </div>

                <h3>No downloads yet</h3>

                <p>
                    Generated exports and reports will appear here.
                </p>

                <span>
                    Create an export from the relevant admin module.
                </span>

            </div>

        @endif

    </section>

</main>


{{-- Automatic Export Status Polling --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {

        const rows = document.querySelectorAll('[data-export-row]');

        if (!rows.length) {
            return;
        }

        const pollRow = async (row) => {

            const url = row.dataset.statusUrl;

            if (!url) {
                return;
            }

            try {

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                const statusCell = row.querySelector('.export-status');
                const recordsCell = row.querySelector('.export-records');
                const completedCell = row.querySelector('.export-completed-at');
                const fileName = row.querySelector('.export-file-name');
                const actionCell = row.querySelector('.export-action');
                const statusWrapper = row.querySelector('.download-status');

                if (recordsCell) {
                    recordsCell.textContent =
                        Number(data.total_records || 0).toLocaleString('en-IN');
                }

                if (fileName && data.file_name) {
                    fileName.textContent = data.file_name;
                }

                if (completedCell && data.completed_at) {
                    completedCell.textContent = data.completed_at;
                }

                if (statusCell) {

                    const labels = {
                        queued: 'Queued',
                        processing: 'Processing',
                        completed: 'Ready',
                        failed: 'Failed'
                    };

                    statusCell.textContent =
                        labels[data.status] || data.status;
                }

                if (statusWrapper) {

                    statusWrapper.className =
                        `download-status download-status--${data.status}`;

                }

                if (data.status === 'completed' && actionCell) {

                    actionCell.innerHTML = `
                        <a
                            href="${data.download_url}"
                            class="download-btn"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3v12"/>
                                <path d="m7 10 5 5 5-5"/>
                                <path d="M5 21h14"/>
                            </svg>
                            <span>Download</span>
                        </a>
                    `;

                    row.dataset.finished = '1';
                }

                if (data.status === 'failed' && actionCell) {

                    actionCell.innerHTML = `
                        <span class="download-failed">
                            Export failed
                        </span>
                    `;

                    row.dataset.finished = '1';
                }

            } catch (error) {
                console.error('Download status update failed:', error);
            }
        };


        const poll = () => {

            rows.forEach(row => {

                if (row.dataset.finished === '1') {
                    return;
                }

                pollRow(row);

            });

        };


        // First check after page load
        poll();

        // Then every 5 seconds
        setInterval(poll, 5000);

    });
</script>


@endsection
