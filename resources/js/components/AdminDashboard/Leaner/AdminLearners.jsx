import { useCallback, useEffect, useMemo, useState } from "react";
import {
    Activity,
    ArrowDownToLine,
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    Plus,
    RefreshCw,
    Search,
    ShieldCheck,
    UsersRound,
    UserRoundX,
    X,
} from "lucide-react";

const API = {
    summary: "/api/admin/learners/summary",
    analytics: "/api/admin/learners/analytics",
    learners: "/api/admin/learners",
    filterOptions: "/api/admin/learners/filter-options",
};

const EMPTY_FILTERS = {
    search: "",
    partner_id: "",
    partner_center_id: "",
    field_agent_id: "",
    course_completed: "",
    status: "",
    program_code: "",
    date_from: "",
    date_to: "",
};

const EMPTY_SUMMARY = {
    total_learners: 0,
    last_seven_days: 0,
    weekly_growth: 0,
    completed_learners: 0,
    unassigned_learners: 0,
    today_learners: 0,
    active_learners: 0,
    pending_learners: 0,
};

const EMPTY_ANALYTICS = {
    registration_trend: [],
};

const PAGE_SIZE = 10;

/**
 * Formats numeric values using the Indian numbering system.
 */
function formatNumber(value) {
    return Number(value ?? 0).toLocaleString("en-IN");
}

/**
 * Safely formats a date for display in the learner table.
 */
function formatDate(value) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "—";

    return date.toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

/**
 * Returns the display name of a learner using the available name fields.
 */
function getLearnerName(learner) {
    const fullName = [learner.first_name, learner.last_name]
        .filter(Boolean)
        .join(" ")
        .trim();

    return fullName || learner.name || "Unnamed Learner";
}

/**
 * Creates initials for the learner avatar.
 */
function getInitials(name = "") {
    return (
        name
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((part) => part.charAt(0))
            .join("")
            .toUpperCase() || "L"
    );
}

/**
 * Extracts a collection from common Laravel API response structures.
 */
function getCollection(result, keys = []) {
    for (const key of keys) {
        const value = result?.[key];

        if (Array.isArray(value)) return value;

        if (Array.isArray(value?.data)) return value.data;
    }

    if (Array.isArray(result?.data)) return result.data;

    return [];
}

/**
 * Reads the Laravel CSRF token for authenticated POST requests.
 */
function getCsrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || ""
    );
}

/**
 * Creates a reusable skeleton card while summary data is loading.
 */
function MetricSkeleton() {
    return (
        <article className="learner-kpi-card learner-kpi-skeleton">
            <span className="learner-skeleton learner-skeleton-icon" />
            <div className="learner-kpi-content">
                <span className="learner-skeleton learner-skeleton-label" />
                <span className="learner-skeleton learner-skeleton-value" />
                <span className="learner-skeleton learner-skeleton-caption" />
            </div>
        </article>
    );
}

/**
 * Displays a metric card with its icon, value and supporting caption.
 */
function MetricCard({ icon: Icon, label, value, caption, warning = false }) {
    return (
        <article
            className={`learner-kpi-card ${
                warning ? "learner-kpi-card-warning" : ""
            }`}
        >
            <div className="learner-kpi-icon">
                <Icon size={21} strokeWidth={1.9} />
            </div>

            <div className="learner-kpi-content">
                <span>{label}</span>
                <strong>{formatNumber(value)}</strong>
                <small>{caption}</small>
            </div>
        </article>
    );
}

/**
 * Displays the seven-day learner registration bar chart.
 */
function RegistrationChart({ data = [], total = 0, loading = false }) {
    const maxValue = Math.max(
        1,
        ...data.map((item) => Number(item.total ?? item.count ?? 0)),
    );

    return (
        <div className="learner-analytics-card">
            <div className="learner-card-heading">
                <div>
                    <span>REGISTRATION ACTIVITY</span>
                    <h2>Last 7 Days</h2>
                </div>

                <div className="learner-card-total">{formatNumber(total)}</div>
            </div>

            {loading ? (
                <div className="learner-chart-loading">
                    <span className="learner-skeleton" />
                    <span className="learner-skeleton" />
                    <span className="learner-skeleton" />
                    <span className="learner-skeleton" />
                    <span className="learner-skeleton" />
                    <span className="learner-skeleton" />
                    <span className="learner-skeleton" />
                </div>
            ) : data.length > 0 ? (
                <div className="learner-chart">
                    {data.map((item, index) => {
                        const count = Number(item.total ?? item.count ?? 0);

                        const height = Math.max(
                            count > 0 ? 5 : 0,
                            (count / maxValue) * 100,
                        );

                        return (
                            <div
                                className="learner-chart-column"
                                key={`${item.date ?? item.label ?? index}`}
                            >
                                <div className="learner-chart-value">
                                    {formatNumber(count)}
                                </div>

                                <div className="learner-chart-track">
                                    <div
                                        className="learner-chart-bar"
                                        style={{ height: `${height}%` }}
                                    />
                                </div>

                                <span>
                                    {item.label ??
                                        (item.date
                                            ? new Date(
                                                  item.date,
                                              ).toLocaleDateString("en-IN", {
                                                  weekday: "short",
                                              })
                                            : "—")}
                                </span>
                            </div>
                        );
                    })}
                </div>
            ) : (
                <div className="learner-chart-empty">
                    No registration activity available.
                </div>
            )}
        </div>
    );
}

/**
 * Displays the learner course-completion progress indicator.
 */
function CompletionChart({
    completed = 0,
    pending = 0,
    total = 0,
    loading = false,
}) {
    const percentage =
        total > 0
            ? Math.min(100, Number(((completed / total) * 100).toFixed(1)))
            : 0;

    return (
        <div className="learner-analytics-card learner-progress-card">
            <div className="learner-card-heading">
                <div>
                    <span>COURSE PROGRESS</span>
                    <h2>Completion Overview</h2>
                </div>
            </div>

            {loading ? (
                <div className="learner-progress-loading">
                    <span className="learner-skeleton" />
                </div>
            ) : (
                <>
                    <div
                        className="learner-progress-circle"
                        style={{
                            background: `conic-gradient(
                                #2865dc ${percentage * 3.6}deg,
                                #e9eef6 ${percentage * 3.6}deg
                            )`,
                        }}
                    >
                        <div>
                            <strong>{percentage}%</strong>
                            <span>Completed</span>
                        </div>
                    </div>

                    <div className="learner-progress-legend">
                        <span>
                            <i className="completed" />
                            Completed
                            <b>{formatNumber(completed)}</b>
                        </span>

                        <span>
                            <i className="pending" />
                            Pending
                            <b>{formatNumber(pending)}</b>
                        </span>
                    </div>
                </>
            )}
        </div>
    );
}

/**
 * Displays the learner table loading placeholders.
 */
function LearnerTableSkeleton() {
    return (
        <tbody>
            {Array.from({ length: PAGE_SIZE }, (_, index) => (
                <tr key={index}>
                    {Array.from({ length: 10 }, (_, cellIndex) => (
                        <td key={cellIndex}>
                            <span className="learner-skeleton learner-table-skeleton" />
                        </td>
                    ))}
                </tr>
            ))}
        </tbody>
    );
}

/**
 * Displays an empty state when no learner matches the current filters.
 */
function EmptyLearners() {
    return (
        <div className="learner-empty">
            <div>
                <UsersRound size={30} />
            </div>
            <strong>No learners found</strong>
            <span>Try changing your search or filters.</span>
        </div>
    );
}

/**
 * Renders the complete Admin Learners management page.
 */
export default function AdminLearners({
    adminName = "Admin",
    canView = true,
    canCreate = false,
    canUpdate = false,
    canDelete = false,
}) {
    const [summary, setSummary] = useState(EMPTY_SUMMARY);
    const [analytics, setAnalytics] = useState(EMPTY_ANALYTICS);

    const [learners, setLearners] = useState([]);
    const [partners, setPartners] = useState([]);
    const [partnerCenters, setPartnerCenters] = useState([]);
    const [fieldAgents, setFieldAgents] = useState([]);

    const [filters, setFilters] = useState(EMPTY_FILTERS);
    const [appliedFilters, setAppliedFilters] = useState(EMPTY_FILTERS);

    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: PAGE_SIZE,
        total: 0,
        from: 0,
        to: 0,
    });

    const [summaryLoading, setSummaryLoading] = useState(true);
    const [analyticsLoading, setAnalyticsLoading] = useState(true);
    const [tableLoading, setTableLoading] = useState(true);

    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");
    const [retryKey, setRetryKey] = useState(0);

    /**
     * Displays a temporary success or information message.
     */
    const showNotice = useCallback((message) => {
        setNotice(message);

        window.setTimeout(() => {
            setNotice("");
        }, 3500);
    }, []);

    /**
     * Updates a filter field without changing the other filter values.
     */
    const updateFilter = (key, value) => {
        setFilters((previous) => ({
            ...previous,
            [key]: value,
        }));
    };

    /**
     * Converts the supplied filter values into URL query parameters.
     */
    const buildQueryParams = (filterValues, page = 1) => {
        const params = new URLSearchParams({
            page: String(page),
            per_page: String(PAGE_SIZE),
        });

        Object.entries(filterValues).forEach(([key, value]) => {
            const normalized = String(value ?? "").trim();

            if (normalized !== "") {
                params.set(key, normalized);
            }
        });

        return params;
    };

    /**
     * Loads summary metrics for the learner management page.
     */
    const loadSummary = async (signal) => {
        setSummaryLoading(true);

        try {
            const response = await fetch(API.summary, {
                credentials: "same-origin",
                headers: { Accept: "application/json" },
                signal,
            });

            if (!response.ok) {
                throw new Error(
                    `Unable to load learner summary (${response.status}).`,
                );
            }

            const result = await response.json();

            if (signal.aborted) return;

            const values = result.summary ?? result.data ?? result;

            setSummary({
                total_learners: Number(
                    values.total_learners ?? values.total ?? 0,
                ),
                last_seven_days: Number(
                    values.last_seven_days ?? values.lastSevenDays ?? 0,
                ),
                weekly_growth: Number(
                    values.weekly_growth ?? values.weeklyGrowth ?? 0,
                ),
                completed_learners: Number(
                    values.completed_learners ?? values.completedLearners ?? 0,
                ),
                unassigned_learners: Number(
                    values.unassigned_learners ??
                        values.unassignedLearners ??
                        0,
                ),
                today_learners: Number(
                    values.today_learners ?? values.todayLearners ?? 0,
                ),
                active_learners: Number(
                    values.active_learners ?? values.activeLearners ?? 0,
                ),
                pending_learners: Number(
                    values.pending_learners ?? values.pendingLearners ?? 0,
                ),
            });
        } catch (err) {
            if (err.name !== "AbortError" && !signal.aborted) {
                setError(err.message || "Unable to load learner summary.");
            }
        } finally {
            if (!signal.aborted) {
                setSummaryLoading(false);
            }
        }
    };

    /**
     * Loads registration activity analytics for the learner page.
     */
    const loadAnalytics = async (signal) => {
        setAnalyticsLoading(true);

        try {
            const response = await fetch(API.analytics, {
                credentials: "same-origin",
                headers: { Accept: "application/json" },
                signal,
            });

            if (!response.ok) {
                throw new Error(
                    `Unable to load learner analytics (${response.status}).`,
                );
            }

            const result = await response.json();

            if (signal.aborted) return;

            const values = result.analytics ?? result.data ?? result;

            setAnalytics({
                registration_trend: getCollection(values, [
                    "registration_trend",
                    "registrationTrend",
                    "trend",
                ]),
            });
        } catch (err) {
            if (err.name !== "AbortError" && !signal.aborted) {
                setError(err.message || "Unable to load learner analytics.");
            }
        } finally {
            if (!signal.aborted) {
                setAnalyticsLoading(false);
            }
        }
    };

    /**
     * Loads available partners, centers and field agents for the filters.
     */
    const loadFilterOptions = async (signal) => {
        try {
            const response = await fetch(API.filterOptions, {
                credentials: "same-origin",
                headers: { Accept: "application/json" },
                signal,
            });

            if (!response.ok) {
                throw new Error("Unable to load learner filter options.");
            }

            const result = await response.json();

            if (signal.aborted) return;

            const values = result.data ?? result;

            setPartners(getCollection(values, ["partners"]));
            setPartnerCenters(
                getCollection(values, [
                    "partner_centers",
                    "partnerCenters",
                    "centers",
                ]),
            );
            setFieldAgents(
                getCollection(values, [
                    "field_agents",
                    "fieldAgents",
                    "agents",
                ]),
            );
        } catch (err) {
            if (err.name !== "AbortError" && !signal.aborted) {
                setError(err.message || "Unable to load filter options.");
            }
        }
    };

    /**
     * Loads the learner list using the active filters and requested page.
     */
    const loadLearners = async (signal, page = 1) => {
        setTableLoading(true);

        try {
            const params = buildQueryParams(appliedFilters, page);

            const response = await fetch(
                `${API.learners}?${params.toString()}`,
                {
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                    signal,
                },
            );

            if (!response.ok) {
                throw new Error(
                    `Unable to load learners (${response.status}).`,
                );
            }

            const result = await response.json();

            if (signal.aborted) return;

            if (result.success === false) {
                throw new Error(
                    result.message || "Unable to load learner records.",
                );
            }

            const paginator =
                result.learners ??
                result.data?.learners ??
                result.data ??
                result;

            const rows = Array.isArray(paginator)
                ? paginator
                : (paginator?.data ?? []);

            setLearners(rows);

            setPagination({
                current_page: Number(
                    paginator?.current_page ?? result.current_page ?? page,
                ),
                last_page: Math.max(
                    1,
                    Number(paginator?.last_page ?? result.last_page ?? 1),
                ),
                per_page: Number(
                    paginator?.per_page ?? result.per_page ?? PAGE_SIZE,
                ),
                total: Number(paginator?.total ?? result.total ?? rows.length),
                from: Number(paginator?.from ?? result.from ?? 0),
                to: Number(paginator?.to ?? result.to ?? rows.length),
            });
        } catch (err) {
            if (err.name !== "AbortError" && !signal.aborted) {
                setError(err.message || "Unable to load learners.");
                setLearners([]);
            }
        } finally {
            if (!signal.aborted) {
                setTableLoading(false);
            }
        }
    };

    /**
     * Loads dashboard metrics, analytics and filter options on page mount.
     */
    useEffect(() => {
        const controller = new AbortController();

        loadSummary(controller.signal);
        loadAnalytics(controller.signal);
        loadFilterOptions(controller.signal);

        return () => controller.abort();
    }, [retryKey]);

    /**
     * Reloads the learner table whenever its page or applied filters change.
     */
    useEffect(() => {
        const controller = new AbortController();

        loadLearners(controller.signal, pagination.current_page);

        return () => controller.abort();
    }, [appliedFilters, pagination.current_page, retryKey]);

    /**
     * Applies the selected filters and resets the table to its first page.
     */
    const applyFilters = (event) => {
        event.preventDefault();

        if (
            filters.date_from &&
            filters.date_to &&
            filters.date_from > filters.date_to
        ) {
            setError("From Date cannot be later than To Date.");
            return;
        }

        setError("");
        setPagination((previous) => ({
            ...previous,
            current_page: 1,
        }));
        setAppliedFilters({ ...filters });
    };

    /**
     * Clears all learner filters and returns to the first page.
     */
    const clearFilters = () => {
        setFilters({ ...EMPTY_FILTERS });
        setAppliedFilters({ ...EMPTY_FILTERS });
        setError("");

        setPagination((previous) => ({
            ...previous,
            current_page: 1,
        }));
    };

    /**
     * Moves the learner table to the requested page.
     */
    const changePage = (page) => {
        const nextPage = Math.max(
            1,
            Math.min(Number(page), pagination.last_page),
        );

        setPagination((previous) => ({
            ...previous,
            current_page: nextPage,
        }));
    };

    /**
     * Submits the current applied filters to the existing Laravel export route.
     */
    const exportLearners = () => {
        const form = document.createElement("form");

        form.method = "POST";
        form.action = "/admin/learners/export";

        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = getCsrfToken();

        form.appendChild(token);

        Object.entries(appliedFilters).forEach(([key, value]) => {
            if (String(value ?? "").trim() === "") return;

            const input = document.createElement("input");

            input.type = "hidden";
            input.name = key;
            input.value = value;

            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        form.remove();
    };

    /**
     * Deletes a learner through the existing Laravel resource route.
     */
    const deleteLearner = async (learner) => {
        const learnerName = getLearnerName(learner);

        const confirmed = window.confirm(
            `Are you sure you want to delete ${learnerName}?`,
        );

        if (!confirmed) return;

        setError("");

        try {
            const response = await fetch(`/admin/learners/${learner.id}`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ _method: "DELETE" }),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    result.message || "Unable to delete this learner.",
                );
            }

            showNotice(result.message || "Learner deleted successfully.");

            setRetryKey((previous) => previous + 1);
        } catch (err) {
            setError(err.message || "Unable to delete this learner.");
        }
    };

    const hasActiveFilters = useMemo(
        () =>
            Object.entries(appliedFilters).some(
                ([, value]) => String(value ?? "").trim() !== "",
            ),
        [appliedFilters],
    );

    const completed = summary.completed_learners;
    const pending = summary.pending_learners;
    const assigned = Math.max(
        0,
        summary.total_learners - summary.unassigned_learners,
    );

    const firstRecord = pagination.total
        ? (pagination.current_page - 1) * pagination.per_page + 1
        : 0;

    const lastRecord = Math.min(
        (pagination.current_page - 1) * pagination.per_page + learners.length,
        pagination.total,
    );

    const exportDisabled = !canView || tableLoading || pagination.total === 0;

    return (
        <main className="admin-learners-page">
            <header className="admin-learners-header">
                <div className="admin-learners-header-left">
                    <a href="/admin" className="admin-back-link">
                        <ArrowLeft size={16} />
                        Dashboard
                    </a>
                </div>

                <div className="admin-learners-header-actions">
                    {canCreate && (
                        <a
                            href="/admin/learners/create"
                            className="learner-btn learner-btn-primary"
                        >
                            <Plus size={17} />
                            Add Learner
                        </a>
                    )}

                    {canView && (
                        <button
                            type="button"
                            className="learner-btn learner-btn-secondary"
                            onClick={exportLearners}
                            disabled={exportDisabled}
                        >
                            <ArrowDownToLine size={17} />
                            Export
                        </button>
                    )}

                    {/* <form method="POST" action="/admin/logout">
                        <input
                            type="hidden"
                            name="_token"
                            value={getCsrfToken()}
                        />

                        <button type="submit" className="learner-signout">
                            Sign out
                        </button>
                    </form> */}
                </div>
            </header>

            <section className="admin-learners-content">
                <div className="admin-learners-title">
                    <div>
                        <p className="learner-eyebrow">LEARNER MANAGEMENT</p>

                        <h1>Learners</h1>

                        <p>
                            Monitor learner onboarding, course progress, partner
                            activity and field agent assignments.
                        </p>
                    </div>

                    <div className="learner-admin-greeting">
                        <span>Logged in as</span>
                        <strong>{adminName}</strong>
                    </div>
                </div>

                {error && (
                    <div
                        className="learner-alert learner-alert-error"
                        role="alert"
                    >
                        <X size={17} />
                        <span>{error}</span>

                        <button
                            type="button"
                            onClick={() => setError("")}
                            aria-label="Dismiss error"
                        >
                            <X size={16} />
                        </button>
                    </div>
                )}

                {notice && (
                    <div
                        className="learner-alert learner-alert-success"
                        role="status"
                    >
                        <CheckCircle2 size={17} />
                        {notice}
                    </div>
                )}

                <section
                    className="learner-kpi-grid"
                    aria-busy={summaryLoading}
                >
                    {summaryLoading ? (
                        <>
                            <MetricSkeleton />
                            <MetricSkeleton />
                            <MetricSkeleton />
                            <MetricSkeleton />
                        </>
                    ) : (
                        <>
                            <MetricCard
                                icon={UsersRound}
                                label="Total Learners"
                                value={summary.total_learners}
                                caption="Across all partners"
                            />

                            <MetricCard
                                icon={Activity}
                                label="Last 7 Days"
                                value={summary.last_seven_days}
                                caption={
                                    <>
                                        <span
                                            className={
                                                summary.weekly_growth >= 0
                                                    ? "positive"
                                                    : "negative"
                                            }
                                        >
                                            {summary.weekly_growth >= 0
                                                ? "↑"
                                                : "↓"}{" "}
                                            {Math.abs(summary.weekly_growth)}%
                                        </span>{" "}
                                        vs previous 7 days
                                    </>
                                }
                            />

                            <MetricCard
                                icon={GraduationCap}
                                label="Course Completed"
                                value={completed}
                                caption={`${
                                    summary.total_learners > 0
                                        ? (
                                              (completed /
                                                  summary.total_learners) *
                                              100
                                          ).toFixed(1)
                                        : 0
                                }% completion rate`}
                            />

                            <MetricCard
                                icon={UserRoundX}
                                label="Unassigned"
                                value={summary.unassigned_learners}
                                caption="Learners without field agent"
                                warning
                            />
                        </>
                    )}
                </section>

                <section className="learner-secondary-stats">
                    <div>
                        <span>Today</span>
                        <strong>
                            {summaryLoading
                                ? "—"
                                : formatNumber(summary.today_learners)}
                        </strong>
                    </div>

                    <div>
                        <span>Active</span>
                        <strong>
                            {summaryLoading
                                ? "—"
                                : formatNumber(summary.active_learners)}
                        </strong>
                    </div>

                    <div>
                        <span>Pending Course</span>
                        <strong>
                            {summaryLoading ? "—" : formatNumber(pending)}
                        </strong>
                    </div>

                    <div>
                        <span>Assigned</span>
                        <strong>
                            {summaryLoading ? "—" : formatNumber(assigned)}
                        </strong>
                    </div>
                </section>

                <section className="learner-analytics-grid">
                    <RegistrationChart
                        data={analytics.registration_trend}
                        total={summary.last_seven_days}
                        loading={analyticsLoading}
                    />

                    <CompletionChart
                        completed={completed}
                        pending={pending}
                        total={summary.total_learners}
                        loading={summaryLoading}
                    />
                </section>

                <section className="learner-table-panel mt-5">
                    <div className="learner-table-heading">
                        <div>
                            <span>LEARNER DIRECTORY</span>
                            <h2>All Learners</h2>
                        </div>

                        <span className="learner-result-count">
                            {formatNumber(pagination.total)} matching records
                        </span>
                    </div>

                    <form className="learner-filters" onSubmit={applyFilters}>
                        <div className="learner-search">
                            <Search size={17} />

                            <input
                                type="search"
                                name="search"
                                value={filters.search}
                                onChange={(event) =>
                                    updateFilter("search", event.target.value)
                                }
                                placeholder="Search name, mobile or program..."
                            />
                        </div>

                        <select
                            name="partner_id"
                            value={filters.partner_id}
                            onChange={(event) =>
                                updateFilter("partner_id", event.target.value)
                            }
                        >
                            <option value="">All Partners</option>

                            {partners.map((partner) => (
                                <option key={partner.id} value={partner.id}>
                                    {partner.name}
                                </option>
                            ))}
                        </select>

                        <select
                            name="partner_center_id"
                            value={filters.partner_center_id}
                            onChange={(event) =>
                                updateFilter(
                                    "partner_center_id",
                                    event.target.value,
                                )
                            }
                        >
                            <option value="">All Centers</option>

                            {partnerCenters.map((center) => (
                                <option key={center.id} value={center.id}>
                                    {center.name}
                                </option>
                            ))}
                        </select>

                        <select
                            name="field_agent_id"
                            value={filters.field_agent_id}
                            onChange={(event) =>
                                updateFilter(
                                    "field_agent_id",
                                    event.target.value,
                                )
                            }
                        >
                            <option value="">All Field Agents</option>

                            {fieldAgents.map((agent) => (
                                <option key={agent.id} value={agent.id}>
                                    {agent.name}
                                </option>
                            ))}
                        </select>

                        <select
                            name="course_completed"
                            value={filters.course_completed}
                            onChange={(event) =>
                                updateFilter(
                                    "course_completed",
                                    event.target.value,
                                )
                            }
                        >
                            <option value="">Course Status</option>
                            <option value="1">Completed</option>
                            <option value="0">Pending</option>
                        </select>

                        <select
                            name="status"
                            value={filters.status}
                            onChange={(event) =>
                                updateFilter("status", event.target.value)
                            }
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        <input
                            type="text"
                            name="program_code"
                            value={filters.program_code}
                            onChange={(event) =>
                                updateFilter("program_code", event.target.value)
                            }
                            placeholder="Program code"
                        />

                        <input
                            type="date"
                            name="date_from"
                            value={filters.date_from}
                            onChange={(event) =>
                                updateFilter("date_from", event.target.value)
                            }
                            aria-label="Registration date from"
                        />

                        <input
                            type="date"
                            name="date_to"
                            value={filters.date_to}
                            onChange={(event) =>
                                updateFilter("date_to", event.target.value)
                            }
                            aria-label="Registration date to"
                        />

                        <div className="learner-filter-actions">
                            <button
                                type="submit"
                                className="learner-btn learner-btn-primary"
                            >
                                <Search size={15} />
                                Apply Filters
                            </button>

                            <button
                                type="button"
                                className="learner-clear-btn"
                                onClick={clearFilters}
                                disabled={!hasActiveFilters && !filters.search}
                            >
                                Clear
                            </button>
                        </div>
                    </form>

                    <div className="learner-table-wrap">
                        <table className="learner-table">
                            <thead>
                                <tr>
                                    <th className="checkbox-column">#</th>
                                    <th>Learner</th>
                                    <th>Mobile</th>
                                    <th>Program</th>
                                    <th>Partner</th>
                                    <th>Field Agent</th>
                                    <th>Location</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            {tableLoading ? (
                                <LearnerTableSkeleton />
                            ) : (
                                <tbody>
                                    {learners.length > 0 ? (
                                        learners.map((learner, index) => {
                                            const name =
                                                getLearnerName(learner);

                                            const completedCourse = Boolean(
                                                Number(
                                                    learner.course_completed ??
                                                        0,
                                                ),
                                            );

                                            const active =
                                                String(
                                                    learner.status ?? "",
                                                ).toLowerCase() === "active";

                                            return (
                                                <tr key={learner.id}>
                                                    <td>
                                                        {firstRecord + index}
                                                    </td>

                                                    <td>
                                                        <div className="learner-person">
                                                            <div className="learner-avatar">
                                                                {getInitials(
                                                                    name,
                                                                )}
                                                            </div>

                                                            <div>
                                                                <strong>
                                                                    {name}
                                                                </strong>

                                                                <small>
                                                                    {learner.gender ||
                                                                        "Gender not specified"}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span className="learner-mobile">
                                                            {learner.normalized_mobile ||
                                                                learner.phone_number ||
                                                                learner.mobile ||
                                                                "—"}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <span className="learner-program">
                                                            {learner.program_code ||
                                                                "—"}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div className="learner-partner">
                                                            <strong>
                                                                {learner.partner
                                                                    ?.name ||
                                                                    learner.partner_name ||
                                                                    "—"}
                                                            </strong>

                                                            <small>
                                                                {learner
                                                                    .partner_center
                                                                    ?.name ||
                                                                    learner
                                                                        .partnerCenter
                                                                        ?.name ||
                                                                    ""}
                                                            </small>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        {learner.field_agent ||
                                                        learner.fieldAgent ? (
                                                            <div className="learner-agent">
                                                                <strong>
                                                                    {learner
                                                                        .field_agent
                                                                        ?.name ||
                                                                        learner
                                                                            .fieldAgent
                                                                            ?.name ||
                                                                        "—"}
                                                                </strong>

                                                                <small>
                                                                    {learner
                                                                        .field_agent
                                                                        ?.sakhi_id ||
                                                                        learner
                                                                            .fieldAgent
                                                                            ?.sakhi_id ||
                                                                        ""}
                                                                </small>
                                                            </div>
                                                        ) : (
                                                            <span className="learner-unassigned">
                                                                Unassigned
                                                            </span>
                                                        )}
                                                    </td>

                                                    <td>
                                                        <div className="learner-location">
                                                            <strong>
                                                                {learner.city ||
                                                                    "—"}
                                                            </strong>

                                                            <small>
                                                                {learner.state ||
                                                                    ""}
                                                            </small>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span
                                                            className={`learner-status ${
                                                                completedCourse
                                                                    ? "learner-status-completed"
                                                                    : "learner-status-pending"
                                                            }`}
                                                        >
                                                            ●{" "}
                                                            {completedCourse
                                                                ? "Completed"
                                                                : "Pending"}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <span
                                                            className={`learner-status ${
                                                                active
                                                                    ? "learner-status-active"
                                                                    : "learner-status-inactive"
                                                            }`}
                                                        >
                                                            ●{" "}
                                                            {learner.status
                                                                ? learner.status
                                                                      .charAt(0)
                                                                      .toUpperCase() +
                                                                  learner.status.slice(
                                                                      1,
                                                                  )
                                                                : "Unknown"}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div className="learner-actions">
                                                            {canUpdate && (
                                                                <a
                                                                    href={`/admin/learners/${learner.id}/edit`}
                                                                    title="Edit learner"
                                                                >
                                                                    Edit
                                                                </a>
                                                            )}

                                                            {canDelete && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        deleteLearner(
                                                                            learner,
                                                                        )
                                                                    }
                                                                    title="Delete learner"
                                                                >
                                                                    Delete
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan="10">
                                                <EmptyLearners />
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            )}
                        </table>
                    </div>

                    <footer className="learner-table-footer">
                        <span>
                            Showing <strong>{firstRecord}</strong> –{" "}
                            <strong>{lastRecord}</strong> of{" "}
                            <strong>{formatNumber(pagination.total)}</strong>
                        </span>

                        <div className="learner-pagination">
                            <button
                                type="button"
                                onClick={() =>
                                    changePage(pagination.current_page - 1)
                                }
                                disabled={
                                    tableLoading || pagination.current_page <= 1
                                }
                                aria-label="Previous page"
                            >
                                <ChevronLeft size={17} />
                            </button>

                            <span>
                                Page {pagination.current_page} of{" "}
                                {pagination.last_page}
                            </span>

                            <button
                                type="button"
                                onClick={() =>
                                    changePage(pagination.current_page + 1)
                                }
                                disabled={
                                    tableLoading ||
                                    pagination.current_page >=
                                        pagination.last_page
                                }
                                aria-label="Next page"
                            >
                                <ChevronRight size={17} />
                            </button>
                        </div>
                    </footer>
                </section>

                <footer className="learner-page-footer">
                    <span>© {new Date().getFullYear()} YouthHub</span>
                    <span>
                        <ShieldCheck size={14} />
                        Admin Workspace
                    </span>
                </footer>
            </section>

            <button
                type="button"
                className="learner-refresh-button"
                onClick={() => setRetryKey((previous) => previous + 1)}
                title="Refresh learner data"
                aria-label="Refresh learner data"
            >
                <RefreshCw size={17} />
            </button>
        </main>
    );
}
