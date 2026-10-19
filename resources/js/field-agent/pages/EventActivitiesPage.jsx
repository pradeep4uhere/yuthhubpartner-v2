
import { useCallback, useEffect, useState } from 'react';
import { CalendarDays, RefreshCw, Search, CheckCircle2 , XCircle, Clock3 , FilePenLine, Plus, FileSpreadsheet  } from 'lucide-react';
import './EventActivitiesPage.css';
import EventActivitiesSkeleton from './EventActivitiesSkeleton';
import EditEventActivityPage from '../pages/EditEventActivityPage';
import AddEventActivityPage from '../pages/AddEventActivityPage';

const API_URL = '/api/field-agent/event-activities';


const REVIEW_STATUSES = [
    'Open',
    'Pending',
    'Accepted',
    'Rejected',
    'Return',
];

const WORKFLOW_STATUSES = [
    'draft',
    'submitted'
];
function formatDate(value, locale = 'en-IN') {
    if (!value) return '—';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleDateString(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatStatus(value, t) {
    if (!value) return t('not_specified');

    const normalized = String(value)
        .replaceAll('_', ' ')
        .toLowerCase();

    const statusKeys = {
        open: 'status_open',
        pending: 'status_pending',
        accepted: 'status_accepted',
        rejected: 'status_rejected',
        return: 'status_return',
        draft: 'status_draft',
        submitted: 'status_submitted',
        under_review: 'status_under_review',
        approved: 'status_approved',
    };

    const key = statusKeys[normalized.replaceAll(' ', '_')];

    return key ? t(key) : normalized.replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function StatusBadge({ value, t }) {
    const normalized = (value || 'unknown')
        .toLowerCase()
        .replaceAll(/[^a-z0-9]+/g, '-');

    return (
        <span className={`ea-status ea-status--${normalized}`}>
            {formatStatus(value, t)}
        </span>
    );
}

function ActivitySummaryCard({ icon: Icon, label, count, tone }) {
    return (
        <div className={`ea-summary ea-summary--${tone}`}>
            <div className="ea-summary-icon">
                <Icon size={22} />
            </div>

            <div className="ea-summary-content">
                <span>{label}</span>
                <strong>{Number(count ?? 0).toLocaleString('en-IN')}</strong>
            </div>
        </div>
    );
}

export default function EventActivitiesPage({
    agent = {},
    onNavigate,
    onEditActivity,
    t = (key) => key,
}) {
        const locale =
            document.documentElement.lang?.toLowerCase().startsWith('hi')
                ? 'hi-IN'
                : 'en-IN';
    const [activities, setActivities] = useState([]);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    const [page, setPage] = useState(1);
    const [reviewStatus, setReviewStatus] = useState('');
    const [workflowStatus, setWorkflowStatus] = useState('');
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [summary, setSummary] = useState({
                total: 0,
                approved: 0,
                rejected: 0,
                open: 0,
                draft: 0,
            });
    const [editingActivity, setEditingActivity] = useState(null);
    const [successMessage, setSuccessMessage] = useState('');
    const [selectedActivity, setSelectedActivity] = useState(null);
    /*
    |--------------------------------------------------------------------------
    | Add Event View State
    |--------------------------------------------------------------------------
    | Tracks whether the Add Event form should replace the activity list.
    |--------------------------------------------------------------------------
    */
    const [addingActivity, setAddingActivity] = useState(false);

    /* --------------------------------------------------------------------------
    | Excel Export State
    | Tracks the export request and prevents duplicate clicks while downloading.
    * -------------------------------------------------------------------------- */
    const [exporting, setExporting] = useState(false);

    const handleEdit = (activity) => {
        setEditingActivity(activity);
    };

    /**
     * Download all event activities matching the current filters as an Excel file.
     *
     * The backend export endpoint must return an Excel (.xlsx) file as a binary
     * response and enforce Field Agent authorization.
     *
     * @returns {Promise<void>}
     */
    const handleExportExcel = async () => {
        setExporting(true);
        setError('');

        try {
            const params = new URLSearchParams();

            if (reviewStatus) {
                params.set('review_status', reviewStatus);
            }

            if (workflowStatus) {
                params.set('workflow_status', workflowStatus);
            }

            if (search.trim()) {
                params.set('search', search.trim());
            }

            const response = await fetch(
                `${API_URL}/export?${params.toString()}`,
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            if (!response.ok) {
                let message = 'Unable to export event activities.';

                try {
                    const result = await response.json();
                    message = result.message || message;
                } catch {
                    // The server may return a non-JSON error response.
                }

                throw new Error(message);
            }

            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = downloadUrl;
            link.download = `event-activities-${new Date()
                .toISOString()
                .slice(0, 10)}.xlsx`;

            document.body.appendChild(link);
            link.click();
            link.remove();

            window.URL.revokeObjectURL(downloadUrl);
        } catch (err) {
            setError(err.message || 'Unable to export event activities.');
        } finally {
            setExporting(false);
        }
    };




    const loadActivities = useCallback(async (signal) => {
        setLoading(true);
        setError('');


        try {
            const params = new URLSearchParams({
                page: String(page),
                per_page: '15',
            });

            if (reviewStatus) {
                params.set('review_status', reviewStatus);
            }

            if (workflowStatus) {
                params.set('workflow_status', workflowStatus);
            }

            const response = await fetch(`${API_URL}?${params.toString()}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal,
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(
                    result.message ||
                    (response.status === 401
                        ? 'Your session has expired. Please log in again.'
                        : 'Unable to load event activities.')
                );
            }

            if (!result.success) {
                throw new Error(
                    result.message || 'Unable to load event activities.'
                );
            }

            setActivities(Array.isArray(result.data) ? result.data : []);
            setSummary({
            total: Number(result.pagination?.total ?? 0),
            approved: (result.data || []).filter(
                (item) => item.review_status === 'Accepted'
            ).length,
            rejected: (result.data || []).filter(
                (item) => item.review_status === 'Rejected'
            ).length,
            open: (result.data || []).filter(
                (item) => item.review_status === 'Open'
            ).length,
            draft: (result.data || []).filter(
                (item) => item.workflow_status === 'Draft'
            ).length,
        });
            setPagination(result.pagination || {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            });
        } catch (err) {
            if (err.name !== 'AbortError') {
                setError(err.message || 'Something went wrong.');
                setActivities([]);
            }
        } finally {
            if (!signal.aborted) {
                setLoading(false);
            }
        }
    }, [page, reviewStatus, workflowStatus]);

    useEffect(() => {
        const controller = new AbortController();
        loadActivities(controller.signal);

        return () => controller.abort();
    }, [loadActivities]);

    const changeReviewStatus = (value) => {
        setPage(1);
        setReviewStatus(value);
    };

    const changeWorkflowStatus = (value) => {
        setPage(1);
        setWorkflowStatus(value);
    };

    /* --------------------------------------------------------------------------
    | Search Visible Activities
    | Search across activity IDs, learner details, and nested event relations.
    * -------------------------------------------------------------------------- */
    const visibleActivities = activities.filter((activity) => {
        const term = search.trim().toLowerCase();

        if (!term) {
            return true;
        }

        return [
            activity.id,
            activity.learner_id,
            activity.beneficiary_name,
            activity.beneficiary_phone_number,
            activity.event_type_name,
            activity.event_category_name,
            activity.event_master_type?.name,
            activity.event_master?.event_name,
            activity.review_status,
            activity.workflow_status,
        ].some((value) =>
            String(value ?? '').toLowerCase().includes(term)
        );
    });


    /*
    |--------------------------------------------------------------------------
    | Render Add Event Form
    |--------------------------------------------------------------------------
    | Opens the creation form and refreshes the activity list after returning
    | from a successful creation.
    |--------------------------------------------------------------------------
    */
    if (addingActivity) {
        return (
            <AddEventActivityPage
                onBack={(message) => {
                    setAddingActivity(false);

                    if (message) {
                        setSuccessMessage(message);
                        setPage(1);
                        loadActivities(new AbortController().signal);
                    }
                }}
                t={t}
            />
        );
    }


    if (selectedActivity) {
        return (
            <EditEventActivityPage
                key={selectedActivity.id}
                activityId={selectedActivity.id}
                onBack={(message) => {
                    setSelectedActivity(null);

                    if (message) {
                        setSuccessMessage(message);
                    }
                }}
                t={t}
            />
        );
    }

    if (loading) {
        return <EventActivitiesSkeleton />;
    }


    return (
        <section className="ea-page">
            {/* --------------------------------------------------------------------------
            | Event Activities Header
            | Keeps the heading on the left and groups all action buttons on the right.
            * -------------------------------------------------------------------------- */}
            <header className="ea-header">
                <div className="ea-header__content">
                    <p className="ea-eyebrow">{t('field_agent_workspace')}</p>
                    <h1>{t('event_activities')}</h1>
                    <p className="ea-subtitle">
                        {t('event_activities_subtitle')}
                    </p>
                </div>

                {/* ----------------------------------------------------------------------
                | Event Activity Header Actions
                | Aligns Refresh, Add Event, and Export Excel consistently in one row.
                * ---------------------------------------------------------------------- */}
                <div className="ea-header__actions">
                    <button
                        type="button"
                        className="ea-refresh-button"
                        onClick={() => loadActivities(new AbortController().signal)}
                        disabled={loading}
                    >
                        <RefreshCw size={16} />
                        <span>{t('refresh')}</span>
                    </button>

                    <button
                        type="button"
                        className="ea-add-button"
                        onClick={() => setAddingActivity(true)}
                    >
                        <Plus size={18} />
                        <span>{t('add_event') || 'Add Event'}</span>
                    </button>

                    <button
                        type="button"
                        className="ea-export-button"
                        onClick={handleExportExcel}
                        disabled={exporting}
                    >
                        <FileSpreadsheet size={18} />
                        <span>
                            {exporting ? 'Exporting...' : 'Export Excel'}
                        </span>
                    </button>
                </div>
            </header>

            <div >

            <div className="ea-summary-grid">
               <ActivitySummaryCard
                    icon={CalendarDays}
                    label={t('total_activities')}
                    count={summary.total}
                    tone="blue"
                />

                <ActivitySummaryCard
                    icon={CheckCircle2}
                    label={t('status_accepted')}
                    count={summary.approved}
                    tone="green"
                />

                <ActivitySummaryCard
                    icon={XCircle}
                    label={t('status_rejected')}
                    count={summary.rejected}
                    tone="red"
                />

                <ActivitySummaryCard
                    icon={Clock3}
                    label={t('status_open')}
                    count={summary.open}
                    tone="amber"
                />

                <ActivitySummaryCard
                    icon={FilePenLine}
                    label={t('status_draft')}
                    count={summary.draft}
                    tone="violet"
                />
            </div>

            </div>
            {successMessage && (
                <div className="ea-success-banner" role="status">
                    <CheckCircle2 size={22} />
                    <div>
                        <strong>Success!</strong>
                        <p>{successMessage}</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setSuccessMessage('')}
                        aria-label="Dismiss success message"
                    >
                        ×
                    </button>
                </div>
            )}

            <div className="card">
            <div className="ea-filters">

                <label className="ea-search">
                    <Search size={17} />
                    <input
                        type="search"
                       placeholder={t('search_activities')}
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </label>

                <label className="ea-filter">

                    <select
                        value={reviewStatus}
                        onChange={(event) =>
                            changeReviewStatus(event.target.value)
                        }
                    >
                        <option value="">{t('all_review_statuses')}</option>
                        {REVIEW_STATUSES.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="ea-filter">

                    <select
                        value={workflowStatus}
                        onChange={(event) =>
                            changeWorkflowStatus(event.target.value)
                        }
                    >
                        <option value="">{t('all_workflow_statuses')}</option>
                        {WORKFLOW_STATUSES.map((status) => (
                            <option key={status} value={status}>
                                 {formatStatus(status, t)}
                            </option>
                        ))}
                    </select>
                </label>
            </div>
            </div>

            {error && (
                <div className="ea-error" role="alert">
                    <p>{error}</p>
                    <button
                        type="button"
                        onClick={() =>
                            loadActivities(new AbortController().signal)
                        }
                    >
                        Try again
                    </button>
                </div>
            )}

            {loading ? (
                    <EventActivitiesSkeleton />
                ) : !error && visibleActivities.length === 0 ? (
                <div className="ea-state">
                    <div className="ea-empty-icon">
                        <CalendarDays size={28} />
                    </div>
                    <h2>
                        {search
                            ? 'No matching activities'
                            : 'No event activities found'}
                    </h2>
                    <p>
                        {search
                            ? 'Try a different search term.'
                            : 'Activities assigned to your Field Agent account will appear here when available.'}
                    </p>
                </div>
            ) : (
                <div className="ea-list">
                    {visibleActivities.map((activity) => (
                        <article className="ea-card" key={activity.id}>
                            <div className="ea-card-top">
                                <div>
                                    <span className="ea-record-id">
                                        Activity #{activity.id}
                                    </span>
                                    <h2>
                                    <strong> {activity.beneficiary_name ?? '—'} | {activity.beneficiary_phone_number || '—'} </strong>
                                    </h2>
                                </div>

                                <StatusBadge
                                    value={activity.review_status}
                                    t={t}
                                />
                            </div>

                            <div className="ea-card-details">
                                <div>
                                    <span>{t('event_type')}</span>
                                    <strong>
                                        {activity.event_master_type?.name ?? '—'}
                                    </strong>
                                </div>
                                <div>
                                    <span>{t('event_category')}</span>
                                    <strong>
                                        {activity.event_master?.event_name ?? '—'}
                                    </strong>
                                </div>
                                <div>
                                    <span>{t('event_date')}</span>
                                    <strong>
                                       {formatDate(activity.event_create_date, locale)}
                                    </strong>
                                </div>
                                <div>
                                    <span>{t('event_value')}</span>
                                    <strong>
                                        {activity.event_value != null
                                            ? `₹${activity.event_value}`
                                            : '—'}
                                    </strong>
                                </div>
                            </div>

                            <div className="ea-card-footer">
                                <div>
                                    <span>{t('workflow')}</span>
                                    <StatusBadge
                                        value={activity.workflow_status}
                                        t={t}
                                    />
                                </div>
                                <span>
                                    {t('submitted')}:{' '}
                                    {formatDate(activity.event_date_submitted, locale)}
                                </span>
                            {/* -----------------------------------------------------------------------
                            | Edit / View Event Button
                            | Approved or accepted activities display a View Event button with
                            | a distinct color. Other activities retain the Edit Event button.
                            * ----------------------------------------------------------------------- */}
                            {(() => {
                                const isApproved = ['accepted', 'approved'].includes(
                                    String(activity.review_status ?? '').trim().toLowerCase()
                                );

                                return (
                                    <button
                                        type="button"
                                        className={`ea-edit-button ${
                                            isApproved ? 'ea-view-button' : ''
                                        }`}
                                        onClick={() => setSelectedActivity(activity)}
                                    >
                                        {isApproved ? (
                                            <CheckCircle2 size={17} strokeWidth={2} />
                                        ) : (
                                            <FilePenLine size={17} strokeWidth={2} />
                                        )}

                                        <span>
                                            {isApproved ? 'View Event' : t('edit_event')}
                                        </span>

                                        <span className="ea-edit-arrow">
                                            →
                                        </span>
                                    </button>
                                );
                            })()}
                            </div>

                            {activity.last_comment && (
                                <p className="ea-comment">
                                    <strong>{t('latest_comment')}:</strong>{' '}
                                    {activity.last_comment}
                                </p>
                            )}
                        </article>
                    ))}
                </div>
            )}

            {!loading && !error && pagination.total > 0 && (
                <nav className="ea-pagination" aria-label="Activity pages">
                    <span>
                        Page {pagination.current_page} of {pagination.last_page}
                    </span>

                    <div>
                        <button
                            type="button"
                            disabled={page <= 1}
                            onClick={() => setPage((current) => current - 1)}
                        >
                            {t('previous')}
                        </button>
                        <button
                            type="button"
                            disabled={page >= pagination.last_page}
                            onClick={() => setPage((current) => current + 1)}
                        >
                            {t('next')}
                        </button>
                    </div>
                </nav>
            )}
        </section>
    );
}
