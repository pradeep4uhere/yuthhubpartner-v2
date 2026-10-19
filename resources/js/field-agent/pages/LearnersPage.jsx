
import React, { useEffect, useState } from 'react';
import {
    Search,
    RefreshCw,
    Users,
    ChevronLeft,
    ChevronRight,
    AlertCircle,
    Phone,
    MapPin,
    Download
} from 'lucide-react';

import './LearnersPage.css';
import LearnerStatusDots from './LearnerStatusDots';

/* --------------------------------------------------------------------------
 | Learners Page Skeleton
 | Mimics the summary cards, search toolbar, and learners table while loading.
 * -------------------------------------------------------------------------- */
function LearnersPageSkeleton() {
    return (
        <div className="fa-learners-skeleton" aria-busy="true" aria-label="Loading learners">
            {/* Summary cards */}
            <div className="fa-skeleton-summary-grid">
                {[1, 2, 3, 4].map((item) => (
                    <div className="fa-skeleton-summary-card" key={item}>
                        <div className="fa-skeleton fa-skeleton-dot" />
                        <div className="fa-skeleton-summary-text">
                            <div className="fa-skeleton fa-skeleton-label" />
                            <div className="fa-skeleton fa-skeleton-number" />
                        </div>
                    </div>
                ))}
            </div>

            {/* Search and filters */}
            <div className="fa-skeleton-toolbar">
                <div className="fa-skeleton fa-skeleton-search" />
                <div className="fa-skeleton fa-skeleton-filter" />
                <div className="fa-skeleton fa-skeleton-filter" />
            </div>

            {/* Table */}
            <div className="fa-skeleton-table-card">
                <div className="fa-skeleton-table-heading">
                    <div className="fa-skeleton fa-skeleton-heading-title" />
                    <div className="fa-skeleton fa-skeleton-heading-subtitle" />
                </div>

                {/* Table header */}
                <div className="fa-skeleton-table-row fa-skeleton-table-header">
                    {[1, 2, 3, 4, 5, 6].map((item) => (
                        <div className="fa-skeleton fa-skeleton-cell" key={item} />
                    ))}
                </div>

                {/* Placeholder learner rows */}
                {Array.from({ length: 7 }, (_, index) => (
                    <div className="fa-skeleton-table-row" key={index}>
                        <div className="fa-skeleton fa-skeleton-cell fa-skeleton-short" />
                        <div className="fa-skeleton fa-skeleton-person">
                            <div className="fa-skeleton fa-skeleton-avatar" />
                            <div className="fa-skeleton-person-lines">
                                <div className="fa-skeleton fa-skeleton-name" />
                                <div className="fa-skeleton fa-skeleton-subtext" />
                            </div>
                        </div>
                        <div className="fa-skeleton fa-skeleton-cell" />
                        <div className="fa-skeleton fa-skeleton-pill" />
                        <div className="fa-skeleton fa-skeleton-cell" />
                        <div className="fa-skeleton fa-skeleton-pill" />
                    </div>
                ))}
            </div>
        </div>
    );
}

/* --------------------------------------------------------------------------
 | API Configuration
 | Update this URL if the Laravel learners endpoint uses a different route.
 * -------------------------------------------------------------------------- */
const API_URL = '/api/field-agent/learners';
const PER_PAGE = 15;

/* --------------------------------------------------------------------------
 | Learner Value Helpers
 | Safely handle common field-name variations in API responses.
 * -------------------------------------------------------------------------- */
const getLearnerName = (learner) =>
    learner.first_name ??
    '—';

const getLearnerPhone = (learner) =>
    learner.normalized_mobile ??
    learner.mobile ??
    learner.phone ??
    learner.mobile_number ??
    '—';

const getLearnerId = (learner) =>
    learner.id ?? learner.learner_id ?? '—';

const getLearnerProgram = (learner) =>
    learner.PROGRAM_CODE ??
    learner.program_code ??
    learner.program_name ??
    learner.program ??
    '—';

const getLearnerInstitute = (learner) =>
    learner.UNIT_INSTITUTE ??
    learner.unit_institute ??
    learner.institute_name ??
    learner.csc_id ??
    '—';

const getLearnerLocation = (learner) => {
    const district = learner.district ?? learner.DISTRICT ?? '';
    const state = learner.state ?? learner.STATE ?? '';

    return [district, state].filter(Boolean).join(', ') || '—';
};

/* --------------------------------------------------------------------------
 | Learners Page
 | Loads field-agent learners with server-side search and pagination.
 * -------------------------------------------------------------------------- */
export default function LearnersPage({ t = (key) => key }) {
    const [learners, setLearners] = useState([]);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    /* --------------------------------------------------------------------------
    | Refresh State
    | Increments whenever the user manually refreshes the learner list.
    * -------------------------------------------------------------------------- */
    const [refreshKey, setRefreshKey] = useState(0);

    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        total: 0,
        per_page: PER_PAGE,
    });

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    const [statusCounts, setStatusCounts] = useState({
        action_required: 0,
        submitted: 0,
        accepted: 0,
        rejected: 0,
    });

    /* --------------------------------------------------------------------------
    | Learner Export State
    | Tracks the progress of exporting all matching learners to CSV.
    * -------------------------------------------------------------------------- */
    const [exporting, setExporting] = useState(false);

    /* ----------------------------------------------------------------------
     | Debounced Search
     | Wait briefly after typing before requesting filtered results.
     * ---------------------------------------------------------------------- */
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            setPage(1);
            setSearch(searchInput.trim());
        }, 350);

        return () => clearTimeout(timeoutId);
    }, [searchInput]);

    /* ----------------------------------------------------------------------
     | Load Learners
     | Expected response: { success, data: [], pagination: {} }
     | Laravel paginator responses containing data.current_page are supported.
     * ---------------------------------------------------------------------- */
    useEffect(() => {
        const controller = new AbortController();

        async function loadLearners() {
            try {
                setLoading(true);
                setError('');

                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(PER_PAGE),
                });

                if (search) {
                    params.set('search', search);
                }

                const response = await fetch(
                    `${API_URL}?${params.toString()}`,
                    {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        `Unable to load learners (HTTP ${response.status}).`
                    );
                }

                /**
                 * Normalize Laravel API responses for both custom JSON pagination
                 * and standard Laravel paginator response formats.
                 */
                const result = await response.json();

                if (result.success === false) {
                    throw new Error(
                        result.message || 'Unable to load learners.'
                    );
                }
                const payload = result.data ?? result;
                const rows = Array.isArray(payload)
                    ? payload
                    : Array.isArray(payload?.data)
                        ? payload.data
                        : [];

                const pageInfo = result.pagination ?? (
                    !Array.isArray(payload) && payload?.current_page
                        ? payload
                        : {}
                );


                /* --------------------------------------------------------------------------
                | Update Summary Counts From Laravel
                | Uses counts calculated across all matching learners, not just this page.
                * -------------------------------------------------------------------------- */
                if (result.status_counts) {
                    setStatusCounts({
                        action_required: Number(result.status_counts.action_required ?? 0),
                        submitted: Number(result.status_counts.submitted ?? 0),
                        accepted: Number(result.status_counts.accepted ?? 0),
                        rejected: Number(result.status_counts.rejected ?? 0),
                    });
                }

                setLearners(rows);

                setPagination({
                    current_page: Number(pageInfo.current_page ?? page),
                    last_page: Math.max(1, Number(pageInfo.last_page ?? 1)),
                    total: Number(pageInfo.total ?? rows.length),
                    per_page: Number(pageInfo.per_page ?? PER_PAGE),
                });

            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    console.error('Learners API error:', fetchError);
                    setError(
                        fetchError.message ||
                        'Something went wrong while loading learners.'
                    );
                    setLearners([]);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }

        loadLearners();

        return () => controller.abort();
    }, [page, search, refreshKey]);

    /* --------------------------------------------------------------------------
    | Refresh Handler
    | Requests the latest learner data for the active page and search.
    * -------------------------------------------------------------------------- */
    const handleRefresh = () => {
        setRefreshKey((key) => key + 1);
    };


    /* --------------------------------------------------------------------------
    | Export All Learners
    | Fetches every page for the current field agent and search, then downloads
    | all matching learner records as an Excel-compatible CSV file.
    * -------------------------------------------------------------------------- */
    const handleExport = async () => {
        if (exporting) return;

        setExporting(true);
        setError('');

        try {
            const exportPerPage = 100;

            const fetchLearnerPage = async (pageNumber) => {
                const params = new URLSearchParams({
                    page: String(pageNumber),
                    per_page: String(exportPerPage),
                });

                if (search) {
                    params.set('search', search);
                }

                const response = await fetch(
                    `${API_URL}?${params.toString()}`,
                    {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        `Export failed while loading page ${pageNumber} (HTTP ${response.status}).`
                    );
                }

                const result = await response.json();

                if (result.success === false) {
                    throw new Error(
                        result.message || 'Unable to export learners.'
                    );
                }

                return {
                    rows: Array.isArray(result.data) ? result.data : [],
                    pagination: result.pagination ?? {},
                };
            };

            // Fetch the first page to determine the total number of pages.
            const firstPage = await fetchLearnerPage(1);
            const allLearners = [...firstPage.rows];
            const lastPage = Number(firstPage.pagination.last_page ?? 1);

            // Fetch all remaining pages, not just the currently visible page.
            for (let pageNumber = 2; pageNumber <= lastPage; pageNumber++) {
                const pageResult = await fetchLearnerPage(pageNumber);
                allLearners.push(...pageResult.rows);
            }

            if (allLearners.length === 0) {
                setError(t('no_learners_to_export'));
                return;
            }

            const columns = [
                { key: 'id', label: 'ID' },
                { key: 'first_name', label: 'First Name' },
                { key: 'last_name', label: 'Last Name' },
                { key: 'normalized_mobile', label: 'Normalized Mobile' },
                { key: 'phone_number', label: 'Phone Number' },
                { key: 'gender', label: 'Gender' },
                { key: 'source_created_at', label: 'Created Date' },
                { key: 'status', label: 'Status' },
                { key: 'PROGRAM_CODE', label: 'Program' },
                { key: 'UNIT_INSTITUTE', label: 'Institute' },
                { key: 'district', label: 'District' },
                { key: 'state', label: 'State' },
                { key: 'skills_status', label: 'Skills Status' },
                { key: 'job_status', label: 'Job Status' },
                {
                    key: 'social_protection_status',
                    label: 'Social Protection Status',
                },
            ];

            // Escape CSV values and mitigate spreadsheet formula injection.
            const escapeCsvValue = (value) => {
                let text = String(value ?? '');

                if (/^[\s]*[=+\-@]/.test(text)) {
                    text = `'${text}`;
                }

                return `"${text.replace(/"/g, '""')}"`;
            };

            const csvRows = [
                columns.map((column) => escapeCsvValue(column.label)).join(','),
                ...allLearners.map((learner) =>
                    columns
                        .map((column) =>
                            escapeCsvValue(learner[column.key])
                        )
                        .join(',')
                ),
            ];

            // BOM ensures Excel recognizes UTF-8 content correctly.
            const csvContent = '\uFEFF' + csvRows.join('\r\n');
            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;',
            });

            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = downloadUrl;
            link.download = `learners_export_${new Date()
                .toISOString()
                .slice(0, 10)}.csv`;

            document.body.appendChild(link);
            link.click();
            link.remove();

            window.URL.revokeObjectURL(downloadUrl);
        } catch (exportError) {
            console.error('Learner export error:', exportError);
            setError(
                exportError.message || 'Unable to export learners.'
            );
        } finally {
            setExporting(false);
        }
    };


    /* --------------------------------------------------------------------------
    | Learners Page Loading State
    | Displays the skeleton while learner data is loading.
    * -------------------------------------------------------------------------- */
    if (loading) {
        return <LearnersPageSkeleton />;
    }

    return (
        <section className="fa-learners-page">
            {/* ----------------------------------------------------------------
             | Page Header
             * ---------------------------------------------------------------- */}
            <div className="fa-learners-header">
                <div className="fa-learners-header__title">
                    <span className="fa-learners-eyebrow">
                        {t('learner_management') === 'learner_management'
                            ? 'LEARNER MANAGEMENT'
                            : t('learner_management')}
                    </span>

                    <h2>
                        {t('learners') === 'learners'
                            ? 'Learners List'
                            : t('learners')}
                    </h2>

                    <p>
                        {t('view_and_search_learners')}
                    </p>
                </div>



                {/* --------------------------------------------------------------------------
                | Header Actions
                | Provides separate controls for refreshing and exporting learner records.
                * -------------------------------------------------------------------------- */}
                <div className="fa-learners-header__actions">
                    <button
                        type="button"
                        className="fa-learners-export"
                        onClick={handleExport}
                        disabled={exporting}
                    >
                        <Download
                            size={16}
                            className={exporting ? 'fa-spinning' : ''}
                        />
                        {exporting ? t('exporting') : t('export_learners')}
                    </button>

                    <button
                        type="button"
                        className="fa-learners-refresh"
                        onClick={handleRefresh}
                        disabled={loading || exporting}
                    >
                        <RefreshCw
                            size={16}
                            className={loading ? 'fa-spinning' : ''}
                        />
                        {t('refresh')}
                    </button>
                </div>
            </div>

            {/* ----------------------------------------------------------------
             | Learner Count and Search
             * ---------------------------------------------------------------- */}
            <div className="fa-learners-toolbar">
                <div className="fa-learners-count">
                    <span className="fa-learners-count__icon">
                        <Users size={20} />
                    </span>

                    <div>
                        <strong>
                            {pagination.total.toLocaleString('en-IN')}
                        </strong>
                        <span>{t('total_learners')}</span>
                    </div>
                </div>

                <label className="fa-learners-search">
                    <Search size={18} />

                    <input
                        type="search"
                        value={searchInput}
                        onChange={(event) =>
                            setSearchInput(event.target.value)
                        }
                        placeholder={t('search_learners_placeholder')}
                        aria-label="{t('search_learners')}"
                    />

                    {searchInput && (
                        <button
                            type="button"
                            onClick={() => setSearchInput('')}
                            aria-label="Clear search"
                        >
                            {t('clear')}
                        </button>
                    )}
                </label>
            </div>

            {/* ----------------------------------------------------------------
             | Error Message
             * ---------------------------------------------------------------- */}
            {error && (
                <div className="fa-learners-error" role="alert">
                    <AlertCircle size={19} />
                    <span>{error}</span>

                    <button type="button" onClick={handleRefresh}>
                       {t('retry')}
                    </button>
                </div>
            )}



                {/*
                |--------------------------------------------------------------------------
                | Learner Status Summary Cards
                |--------------------------------------------------------------------------
                | Displays dashboard-style counts for Action Required, Submitted,
                | Accepted, and Rejected learners.
                | Replace the example count values with counts calculated from your API.
                */}

                <div className="learner-summary-grid">
                    <div className="learner-summary-card action-required-card">
                        <div className="learner-summary-info">
                            <span className="learner-summary-label">{t('action_required')}</span>
                            <h3>{statusCounts?.action_required ?? 0}</h3>
                            <span className="learner-summary-caption">{t('needs_attention')}</span>
                        </div>
                        <div className="learner-summary-icon">
                            <i className="fas fa-clock" aria-hidden="true" />
                        </div>
                    </div>

                   {/* --------------------------------------------------------------------------
                    | Submitted Learners Summary Card
                    * -------------------------------------------------------------------------- */}
                    <div className="learner-summary-card submitted-card">
                        <div className="learner-summary-info">
                            <span className="learner-summary-label">
                                {t('submitted')}
                            </span>
                            <h3>{statusCounts?.submitted ?? 0}</h3>
                            <span className="learner-summary-caption">
                                {t('awaiting_review')}
                            </span>
                        </div>
                        <div className="learner-summary-icon">
                            <i className="fas fa-paper-plane" aria-hidden="true" />
                        </div>
                    </div>

                    {/* --------------------------------------------------------------------------
                    | Accepted Learners Summary Card
                    * -------------------------------------------------------------------------- */}
                    <div className="learner-summary-card accepted-card">
                        <div className="learner-summary-info">
                            <span className="learner-summary-label">
                                {t('accepted')}
                            </span>
                            <h3>{statusCounts?.accepted ?? 0}</h3>
                            <span className="learner-summary-caption">
                                {t('successfully_accepted')}
                            </span>
                        </div>
                        <div className="learner-summary-icon">
                            <i className="fas fa-check-circle" aria-hidden="true" />
                        </div>
                    </div>

                    {/* --------------------------------------------------------------------------
                    | Rejected Learners Summary Card
                    * -------------------------------------------------------------------------- */}
                    <div className="learner-summary-card rejected-card">
                        <div className="learner-summary-info">
                            <span className="learner-summary-label">
                                {t('rejected')}
                            </span>
                            <h3>{statusCounts?.rejected ?? 0}</h3>
                            <span className="learner-summary-caption">
                                {t('requires_follow_up')}
                            </span>
                        </div>
                        <div className="learner-summary-icon">
                            <i className="fas fa-times-circle" aria-hidden="true" />
                        </div>
                    </div>
                </div>




                {/* --------------------------------------------------------------------------
                | Learner Activity Status Legend
                | Displays translated activity status labels and learner activity names.
                * -------------------------------------------------------------------------- */}
                <div className="learner-legend-card">
                    <div className="learner-legend-header">
                        <div className="learner-legend-heading">
                            <span className="learner-legend-icon">
                                <i className="fas fa-chart-simple" aria-hidden="true" />
                            </span>

                            <div>
                                <h6>{t('activity_status')}</h6>
                                <p>{t('understand_learner_progress')}</p>
                            </div>
                        </div>

                        <span className="learner-legend-count">
                            {t('activities_count', { count: 3 })}
                        </span>
                    </div>

                    <div className="learner-legend-content">
                        <div className="learner-legend-statuses">
                            <span className="learner-summary-card action-required-card">
                                <span className="legend-dot action-required" />
                                <span>{t('action_required')}</span>
                            </span>

                            <span className="learner-summary-card submitted-card">
                                <span className="legend-dot submitted" />
                                <span>{t('submitted')}</span>
                            </span>

                            <span className="learner-summary-card accepted-card">
                                <span className="legend-dot accepted" />
                                <span>{t('accepted')}</span>
                            </span>

                            <span className="learner-summary-card rejected-card">
                                <span className="legend-dot rejected" />
                                <span>{t('rejected')}</span>
                            </span>
                        </div>

                        <div className="learner-legend-divider" />

                        <div className="learner-legend-activities">
                            <div className="learner-legend-activity">
                                <span className="learner-legend-status">
                                    <span>{t('first_skills')}</span>
                                </span>
                            </div>

                            <div className="learner-legend-activity">
                                <span className="learner-legend-status">
                                    <span>{t('second_job')}</span>
                                </span>
                            </div>

                            <div className="learner-legend-activity">
                                <span className="learner-legend-status">
                                    <span>{t('third_social_protection')}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>


            {/* ----------------------------------------------------------------
             | Learners Table
             * ---------------------------------------------------------------- */}
            <div className="fa-learners-table-card">
                <div className="fa-learners-table-scroll">
                {/* Learner listing table: compact columns for mobile screens */}
                <table className="learners-table">
                    <thead>
                       <tr>
                            <th>{t('id')}</th>
                            <th>{t('name')}</th>
                            <th>{t('contact')}</th>

                            {/* Visible on desktop only */}
                            <th className="desktop-only">{t('gender')}</th>
                            <th className="desktop-only">{t('created_date')}</th>
                            <th className="desktop-only">{t('status')}</th>

                            {/* Visible on both desktop and mobile */}
                            <th className="activity-status-column">{t('activity')}</th>
                        </tr>
                    </thead>

                    <tbody>
                        {learners.map((learner) => (
                            <tr key={learner.id}>
                                <td>{learner.id}</td>
                                <td className="learner-name-cell">
                                    {learner.first_name}
                                </td>
                                <td className="learner-contact-cell">
                                    {learner.normalized_mobile || learner.phone_number || '-'}
                                </td>

                                {/* Keep these existing data fields as appropriate */}
                                <td className="desktop-only">
                                    {learner.gender || '-'}
                                </td>
                                <td className="desktop-only">
                                    {learner.source_created_at || '-'}
                                </td>
                                <td className="desktop-only">
                                    {learner.status || '-'}
                                </td>

                                <td className="activity-status-column">
                                    <LearnerStatusDots learner={learner} />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                </div>

                {/* ------------------------------------------------------------
                 | Pagination
                 * ------------------------------------------------------------ */}
                <div className="fa-learners-pagination">
                    <span>
                        {pagination.total > 0
                            ? `Page ${pagination.current_page} of ${pagination.last_page} · ${pagination.total.toLocaleString('en-IN')} learners`
                            : 'No records'}
                    </span>

                    <div className="fa-learners-pagination__actions">
                        <button
                            type="button"
                            onClick={() =>
                                setPage((currentPage) =>
                                    Math.max(1, currentPage - 1)
                                )
                            }
                            disabled={
                                loading ||
                                page <= 1
                            }
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                            {t('previous')}
                        </button>

                        <button
                            type="button"
                            onClick={() =>
                                setPage((currentPage) =>
                                    Math.min(
                                        pagination.last_page,
                                        currentPage + 1
                                    )
                                )
                            }
                            disabled={
                                loading ||
                                page >= pagination.last_page
                            }
                            aria-label="Next page"
                        >
                            {t('next')}
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </div>
            </div>
        </section>
    );
}
