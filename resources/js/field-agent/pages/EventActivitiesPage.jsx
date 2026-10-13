
import { useCallback, useEffect, useState } from 'react';
import { CalendarDays, RefreshCw, Search, CheckCircle2 , XCircle, Clock3 , FilePenLine  } from 'lucide-react';
import './EventActivitiesPage.css';

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
    'submitted',
    'under_review',
    'approved',
    'rejected',
];

function formatDate(value) {
    if (!value) return '—';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatStatus(value) {
    if (!value) return 'Not specified';

    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function StatusBadge({ value }) {
    const normalized = (value || 'unknown')
        .toLowerCase()
        .replaceAll(/[^a-z0-9]+/g, '-');

    return (
        <span className={`ea-status ea-status--${normalized}`}>
            {formatStatus(value)}
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


export default function EventActivitiesPage({ agent = {}, onNavigate }) {
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

    const visibleActivities = activities.filter((activity) => {
        const term = search.trim().toLowerCase();

        if (!term) return true;

        return [
            activity.id,
            activity.learner_id,
            activity.beneficiary_name,
            activity.beneficiary_phone_number,
            activity.event_type_name,
            activity.event_category_name,
        ].some((value) =>
            String(value ?? '').toLowerCase().includes(term)
        );
    });

    return (
        <section className="ea-page">
            <header className="ea-header">
                <div>
                    <p className="ea-eyebrow">FIELD AGENT WORKSPACE</p>
                    <h1>Event Activities</h1>
                    <p className="ea-subtitle">
                        View and track your event activity records.
                    </p>
                </div>

                <button
                    type="button"
                    className="ea-refresh-button"
                    onClick={() => loadActivities(new AbortController().signal)}
                    disabled={loading}
                >
                    <RefreshCw size={16} />
                    Refresh
                </button>
            </header>

            <div >
                        
            <div className="ea-summary-grid">
                <ActivitySummaryCard
                    icon={CalendarDays}
                    label="Total Activities"
                    count={summary.total}
                    tone="blue"
                />

                <ActivitySummaryCard
                    icon={CheckCircle2}
                    label="Accepted"
                    count={summary.approved}
                    tone="green"
                />

                <ActivitySummaryCard
                    icon={XCircle}
                    label="Rejected"
                    count={summary.rejected}
                    tone="red"
                />

                <ActivitySummaryCard
                    icon={Clock3}
                    label="Open"
                    count={summary.open}
                    tone="amber"
                />

                <ActivitySummaryCard
                    icon={FilePenLine}
                    label="Draft"
                    count={summary.draft}
                    tone="violet"
                />
            </div>

            </div>

            <div className="ea-filters">
                
                <label className="ea-search">
                      
                    <Search size={17} />
                    <input
                        type="search"
                        placeholder="Search name, learner ID, event..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                </label>

                <label className="ea-filter">
                    <span>Review status</span>
                    <select
                        value={reviewStatus}
                        onChange={(event) =>
                            changeReviewStatus(event.target.value)
                        }
                    >
                        <option value="">All review statuses</option>
                        {REVIEW_STATUSES.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="ea-filter">
                    <span>Workflow status</span>
                    <select
                        value={workflowStatus}
                        onChange={(event) =>
                            changeWorkflowStatus(event.target.value)
                        }
                    >
                        <option value="">All workflow statuses</option>
                        {WORKFLOW_STATUSES.map((status) => (
                            <option key={status} value={status}>
                                {formatStatus(status)}
                            </option>
                        ))}
                    </select>
                </label>
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
                <div className="ea-state">
                    <span className="ea-spinner" />
                    <p>Loading event activities...</p>
                </div>
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
                                        {activity.event_type_name ||
                                            activity.event_category_name ||
                                            'Event activity'}
                                    </h2>
                                </div>

                                <StatusBadge
                                    value={activity.review_status}
                                />
                            </div>

                            <div className="ea-card-details">
                                <div>
                                    <span>Learner ID</span>
                                    <strong>
                                        {activity.learner_id ?? '—'}
                                    </strong>
                                </div>
                                <div>
                                    <span>Beneficiary</span>
                                    <strong>
                                        {activity.beneficiary_name || '—'}
                                    </strong>
                                </div>
                                <div>
                                    <span>Event date</span>
                                    <strong>
                                        {formatDate(activity.event_create_date)}
                                    </strong>
                                </div>
                                <div>
                                    <span>Event value</span>
                                    <strong>
                                        {activity.event_value != null
                                            ? `₹${activity.event_value}`
                                            : '—'}
                                    </strong>
                                </div>
                            </div>

                            <div className="ea-card-footer">
                                <div>
                                    <span>Workflow</span>
                                    <StatusBadge
                                        value={activity.workflow_status}
                                    />
                                </div>
                                <span>
                                    Submitted:{' '}
                                    {formatDate(activity.event_date_submitted)}
                                </span>
                            </div>

                            {activity.last_comment && (
                                <p className="ea-comment">
                                    <strong>Latest comment:</strong>{' '}
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
                            Previous
                        </button>
                        <button
                            type="button"
                            disabled={page >= pagination.last_page}
                            onClick={() => setPage((current) => current + 1)}
                        >
                            Next
                        </button>
                    </div>
                </nav>
            )}
        </section>
    );
}
