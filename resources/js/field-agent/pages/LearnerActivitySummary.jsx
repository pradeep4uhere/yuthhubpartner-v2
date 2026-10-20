
import React, { useEffect, useState } from 'react';
import {
    Activity,
    CheckCircle2,
    Clock3,
    Send,
    XCircle,
    RefreshCw,
    AlertCircle,
} from 'lucide-react';

import './LearnerActivitySummary.css';

/* --------------------------------------------------------------------------
 | Activity Summary API
 | Fetches aggregate activity counts independently of learner pagination.
 * -------------------------------------------------------------------------- */
const ACTIVITY_COUNTS_API = '/api/field-agent/learners/activity-counts';

/* --------------------------------------------------------------------------
 | Activity Definitions
 | Maps API keys to readable activity titles and their status indicators.
 * -------------------------------------------------------------------------- */
/* --------------------------------------------------------------------------
 | Activity Definitions
 | Stores translation keys instead of hardcoded visible labels.
 * -------------------------------------------------------------------------- */
const ACTIVITIES = [
    {
        key: 'skills',
        titleKey: 'first_skills',
        statuses: [
            { key: 'accepted', labelKey: 'accepted', color: '#16a34a', icon: CheckCircle2 },
            { key: 'not_started', labelKey: 'not_started', color: '#94a3b8', icon: Clock3 },
        ],
    },
    {
        key: 'job',
        titleKey: 'second_job',
        statuses: [
            { key: 'accepted', labelKey: 'accepted', color: '#16a34a', icon: CheckCircle2 },
            { key: 'pending', labelKey: 'pending', color: '#d97706', icon: Clock3 },
            { key: 'submitted', labelKey: 'submitted', color: '#2563eb', icon: Send },
            { key: 'rejected', labelKey: 'rejected', color: '#dc2626', icon: XCircle },
            { key: 'not_started', labelKey: 'not_started', color: '#94a3b8', icon: Activity },
        ],
    },
    {
        key: 'social_protection',
        titleKey: 'third_social_protection',
        statuses: [
            { key: 'accepted', labelKey: 'accepted', color: '#16a34a', icon: CheckCircle2 },
            { key: 'pending', labelKey: 'pending', color: '#d97706', icon: Clock3 },
            { key: 'submitted', labelKey: 'submitted', color: '#2563eb', icon: Send },
            { key: 'rejected', labelKey: 'rejected', color: '#dc2626', icon: XCircle },
            { key: 'not_started', labelKey: 'not_started', color: '#94a3b8', icon: Activity },
        ],
    },
];

/* --------------------------------------------------------------------------
 | Animated Activity Wave
 | Renders a continuously moving ECG-style line for the activity heading.
 * -------------------------------------------------------------------------- */
/* --------------------------------------------------------------------------
 | Animated Activity Wave
 | Creates a continuously moving ECG waveform without a visible pause.
 * -------------------------------------------------------------------------- */
function AnimatedActivityWave() {
    return (
        <svg
            className="fa-activity-wave"
            width="28"
            height="24"
            viewBox="0 0 28 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <defs>
                <clipPath id="activity-wave-clip">
                    <rect width="28" height="24" />
                </clipPath>
            </defs>

            <g clipPath="url(#activity-wave-clip)">
                <path
                    className="fa-activity-wave__track"
                    d="M0 12 H28"
                />

                <path
                    className="fa-activity-wave__line"
                    d="M-28 12 H-20 L-16 12 L-12 5 L-7 19 L-2 8 L2 12 H8 L12 12 L16 5 L21 19 L26 8 L30 12 H36"
                />
            </g>
        </svg>
    );
}

/* --------------------------------------------------------------------------
 | Learner Activity Summary
 | Loads aggregate counts for all matching learners, not the current page.
 | Search filters are shared with LearnersPage; pagination is intentionally
 | excluded so changing pages does not change activity totals.
 * -------------------------------------------------------------------------- */
export default function LearnerActivitySummary({
    t = (key) => key,
    search = '',
    refreshKey = 0,
}) {
    const [activityCounts, setActivityCounts] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [retryKey, setRetryKey] = useState(0);

    useEffect(() => {
        const controller = new AbortController();

        /**
         * Fetch aggregate activity counts for the authenticated field agent.
         *
         * Sends the current search filter but never sends a page number or
         * per-page limit. Aborts stale requests when filters or refresh state
         * change, and preserves the last successful counts on request failure.
         *
         * @returns {Promise<void>}
         */
        async function loadActivityCounts() {
            setLoading(true);
            setError('');

            try {
                const params = new URLSearchParams();

                if (search.trim()) {
                    params.set('search', search.trim());
                }

                const queryString = params.toString();
                const url = queryString
                    ? `${ACTIVITY_COUNTS_API}?${queryString}`
                    : ACTIVITY_COUNTS_API;

                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error(
                        `Unable to load activity counts (HTTP ${response.status}).`
                    );
                }

                const result = await response.json();

                if (result.success === false) {
                    throw new Error(
                        result.message || 'Unable to load activity counts.'
                    );
                }

                if (!result.activity_counts) {
                    throw new Error(
                        'The API response does not contain activity_counts.'
                    );
                }

                setActivityCounts(result.activity_counts);
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    console.error('Activity counts API error:', fetchError);
                    setError(
                        fetchError.message || 'Unable to load activity summary.'
                    );
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }

        loadActivityCounts();

        return () => controller.abort();
    }, [search, refreshKey, retryKey]);

    /* ----------------------------------------------------------------------
     | Loading State
     | Keeps the activity summary independent from the learners table.
     * ---------------------------------------------------------------------- */
    if (loading && !activityCounts) {
        return (
            <section className="fa-activity-summary" aria-busy="true">
                <div className="fa-activity-summary__heading">
                    <AnimatedActivityWave />

                    <div>
                        <h3>{t('activity_status')}</h3>
                        <p>{t('loading_activity_counts')}</p>
                    </div>
                </div>

                <div className="fa-activity-summary__grid">
                    {ACTIVITIES.map((activity) => (
                        <div
                            className="fa-activity-card fa-activity-card--loading"
                            key={activity.key}
                        >
                            <div className="fa-activity-skeleton" />
                            <div className="fa-activity-skeleton fa-activity-skeleton--short" />
                            <div className="fa-activity-skeleton" />
                        </div>
                    ))}
                </div>
            </section>
        );
    }

    /* ----------------------------------------------------------------------
     | Error State
     | Offers retry without blocking the learner listing.
     * ---------------------------------------------------------------------- */
    if (error && !activityCounts) {
        return (
                <section className="fa-activity-summary">
                    <div className="fa-activity-error" role="alert">
                        <AlertCircle size={19} />

                        <span>{error}</span>

                        <button
                            type="button"
                            onClick={() => setRetryKey((key) => key + 1)}
                        >
                            {t('retry')}
                        </button>
                    </div>
                </section>
        );
    }

    return (
            <section className="fa-activity-summary">
                <div className="fa-activity-summary__heading">
                    <div className="fa-activity-summary__title">
                        <AnimatedActivityWave />

                        <div>
                            <h3>{t('activity_status')}</h3>
                            <p>{t('understand_learner_progress')}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        className="fa-activity-refresh"
                        onClick={() => setRetryKey((key) => key + 1)}
                        disabled={loading}
                        aria-label={t('refresh_activity_counts')}
                        title={t('refresh_activity_counts')}
                    >
                        <RefreshCw
                            size={17}
                            className={loading ? 'fa-activity-spinning' : ''}
                        />
                    </button>
                </div>

                {error && (
                    <div className="fa-activity-inline-error" role="alert">
                        {error} {t('showing_last_successful_counts')}
                    </div>
                )}

                <div className="fa-activity-summary__grid">
                    {ACTIVITIES.map((activity) => {
                        const counts = activityCounts?.[activity.key] ?? {};
                        const total = Number(counts.total ?? 0);
                        const activityTitle = t(activity.titleKey);

                        return (
                            <article
                                className="fa-activity-card"
                                key={activity.key}
                            >
                                <div className="fa-activity-card__heading">
                                    <h4>{activityTitle}</h4>

                                    <span>
                                        {t('learners_count', { count: total.toLocaleString('en-IN') })}
                                    </span>
                                </div>

                                <div
                                    className="fa-activity-progress"
                                    role="img"
                                    aria-label={t('activity_status_distribution', {
                                        activity: activityTitle,
                                    })}
                                >
                                    {activity.statuses.map((status) => {
                                        const count = Number(counts[status.key] ?? 0);

                                        const percentage = total > 0
                                            ? (count / total) * 100
                                            : 0;

                                        return percentage > 0 ? (
                                            <span
                                                key={status.key}
                                                className="fa-activity-progress__segment"
                                                style={{
                                                    width: `${percentage}%`,
                                                    backgroundColor: status.color,
                                                }}
                                                title={`${t(status.labelKey)}: ${count.toLocaleString('en-IN')}`}
                                            />
                                        ) : null;
                                    })}
                                </div>

                                <div className="fa-activity-status-list">
                                    {activity.statuses.map((status) => {
                                        const count = Number(counts[status.key] ?? 0);

                                        const percentage = total > 0
                                            ? (count / total) * 100
                                            : 0;

                                        const StatusIcon = status.icon;

                                        return (
                                            <div
                                                className="fa-activity-status-row"
                                                key={status.key}
                                            >
                                                <span className="fa-activity-status-label">
                                                    <StatusIcon
                                                        size={15}
                                                        style={{ color: status.color }}
                                                    />

                                                    {t(status.labelKey)}
                                                </span>

                                                <span className="fa-activity-status-value">
                                                    {count.toLocaleString('en-IN')}

                                                    <small>
                                                        {` ${percentage.toFixed(1)}%`}
                                                    </small>
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </article>
                        );
                    })}
                </div>
            </section>
    );
}
