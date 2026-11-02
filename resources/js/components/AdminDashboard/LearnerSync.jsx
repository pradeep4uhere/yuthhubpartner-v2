import { useCallback, useEffect, useMemo, useState } from "react";
import {
    Activity,
    AlertCircle,
    CheckCircle2,
    Clock3,
    Database,
    FileWarning,
    History,
    Layers3,
    Play,
    RefreshCw,
    Search,
    ShieldAlert,
    XCircle,
} from "lucide-react";

import Sidebar from "./Sidebar";
import DashboardHeader from "./DashboardHeader";
import "../../../css/admin-learner-sync.css";

/**
 * Format numeric dashboard values using the Indian numbering system.
 */
const formatNumber = (value) => Number(value ?? 0).toLocaleString("en-IN");

/**
 * Format a date or timestamp for the synchronization dashboard.
 */
const formatDate = (value) => {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "—";

    return date.toLocaleString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

/**
 * Convert a synchronization status into its visual label.
 */
const getStatusLabel = (status) => {
    const labels = {
        running: "Running",
        completed: "Completed",
        failed: "Failed",
        pending: "Pending",
    };

    return labels[status] ?? status ?? "Unknown";
};

/**
 * Render a compact metric card for synchronization statistics.
 */
function SyncMetric({ label, value, icon: Icon, tone }) {
    return (
        <div className={`ls-metric ls-metric--${tone}`}>
            <div className="ls-metric__top">
                <span>{label}</span>
                <span className="ls-metric__icon">
                    <Icon size={19} />
                </span>
            </div>

            <strong>{formatNumber(value)}</strong>
        </div>
    );
}

/**
 * Display a status badge with a matching visual state.
 */
function SyncStatus({ status }) {
    return (
        <span className={`ls-status ls-status--${status || "unknown"}`}>
            <span className="ls-status__dot" />
            {getStatusLabel(status)}
        </span>
    );
}

/**
 * Display a skeleton placeholder while synchronization data is loading.
 */
function SyncSkeleton() {
    return (
        <div className="ls-skeleton-grid">
            {[1, 2, 3, 4, 5, 6].map((item) => (
                <div className="ls-skeleton-card" key={item}>
                    <span />
                    <strong />
                </div>
            ))}
        </div>
    );
}

/**
 * Render the Admin Learner Synchronization dashboard.
 */
export default function LearnerSync({ adminName = "Admin", csrfToken = "" }) {
    const [dashboardData, setDashboardData] = useState({
        active_run: null,
        recent_runs: [],
        latest_run: null,
        summary: {},
    });

    const [selectedRunId, setSelectedRunId] = useState(null);
    const [runDetails, setRunDetails] = useState(null);
    const [pageLogs, setPageLogs] = useState([]);
    const [recordLogs, setRecordLogs] = useState([]);

    const [recordType, setRecordType] = useState("");
    const [recordSearch, setRecordSearch] = useState("");
    const [activeTab, setActiveTab] = useState("pages");

    const [loading, setLoading] = useState(true);
    const [starting, setStarting] = useState(false);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");

    /**
     * Fetch JSON from an admin synchronization endpoint.
     */
    const fetchJson = useCallback(async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                ...options.headers,
            },
            ...options,
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                result.message || "Unable to complete the request.",
            );
        }

        return result;
    }, []);

    /**
     * Load recent synchronization runs and identify the latest or active run.
     */
    const fetchDashboardData = useCallback(async () => {
        const result = await fetchJson("/admin/learner-sync/data");

        if (!result.success) {
            throw new Error(result.message || "Unable to load sync history.");
        }

        // Read synchronization history from the Laravel paginator response.
        const recentRuns = result.runs?.data ?? [];

        // Prefer the active run; otherwise select the latest completed or failed run.
        const latestRun =
            result.active_run ?? result.latest_run ?? recentRuns[0] ?? null;

        // Store dashboard data using the actual Laravel API response structure.
        setDashboardData({
            active_run: result.active_run ?? null,
            recent_runs: recentRuns,
            latest_run: latestRun,
            summary: result.summary ?? {},
        });

        // Select the latest run automatically when no run is already selected.
        setSelectedRunId((currentId) => {
            if (currentId) {
                return currentId;
            }

            return latestRun?.id ?? null;
        });
    }, [fetchJson]);

    /**
     * Load status, page logs, and record logs for the selected run.
     */
    const fetchRunDetails = useCallback(
        async (runId) => {
            if (!runId) return;

            const [statusResult, pagesResult, recordsResult] =
                await Promise.all([
                    fetchJson(`/admin/learner-sync/${runId}/status`),
                    fetchJson(`/admin/learner-sync/${runId}/pages`),
                    fetchJson(
                        `/admin/learner-sync/${runId}/records?type=${encodeURIComponent(recordType)}&search=${encodeURIComponent(recordSearch)}`,
                    ),
                ]);

            setRunDetails(statusResult.run ?? null);
            setPageLogs(pagesResult.pages?.data ?? []);
            setRecordLogs(recordsResult.records?.data ?? []);
        },
        [fetchJson, recordType, recordSearch],
    );

    /**
     * Load the dashboard when the page opens and refresh recent runs periodically.
     */
    useEffect(() => {
        let isMounted = true;

        /**
         * Safely load dashboard data and update the loading state.
         */
        const loadDashboard = async () => {
            try {
                await fetchDashboardData();

                if (isMounted) {
                    setError("");
                }
            } catch (requestError) {
                if (isMounted) {
                    setError(requestError.message);
                }
            } finally {
                if (isMounted) {
                    setLoading(false);
                }
            }
        };

        loadDashboard();

        const intervalId = window.setInterval(loadDashboard, 10000);

        return () => {
            isMounted = false;
            window.clearInterval(intervalId);
        };
    }, [fetchDashboardData]);

    /**
     * Refresh selected run details while the dashboard remains open.
     */
    useEffect(() => {
        if (!selectedRunId) return undefined;

        let isMounted = true;

        /**
         * Fetch the selected run and its related logs.
         */
        const loadSelectedRun = async () => {
            try {
                await fetchRunDetails(selectedRunId);

                if (isMounted) {
                    setError("");
                }
            } catch (requestError) {
                if (isMounted) {
                    setError(requestError.message);
                }
            }
        };

        loadSelectedRun();

        const intervalId = window.setInterval(loadSelectedRun, 5000);

        return () => {
            isMounted = false;
            window.clearInterval(intervalId);
        };
    }, [selectedRunId, fetchRunDetails]);

    /**
     * Start an incremental or full learner synchronization.
     */
    const handleStartSync = async (syncType) => {
        if (starting || dashboardData.active_run) return;

        if (
            syncType === "full" &&
            !window.confirm(
                "Full Sync may process the complete YouthHub learner dataset and take considerable time. Do you want to continue?",
            )
        ) {
            return;
        }

        setStarting(true);
        setError("");
        setNotice("");

        try {
            const result = await fetchJson("/admin/learner-sync/run", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    sync_type: syncType,
                }),
            });

            setNotice(result.message || "Synchronization queued successfully.");

            if (result.run?.id) {
                setSelectedRunId(result.run.id);
            }

            await fetchDashboardData();
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setStarting(false);
        }
    };

    /**
     * Refresh dashboard history and the currently selected synchronization run.
     */
    const handleRefresh = async () => {
        try {
            setError("");
            await fetchDashboardData();

            if (selectedRunId) {
                await fetchRunDetails(selectedRunId);
            }

            setNotice("Synchronization data refreshed.");
        } catch (requestError) {
            setError(requestError.message);
        }
    };

    /**
     * Select a synchronization run from the recent history list.
     */
    const handleSelectRun = (runId) => {
        setSelectedRunId(runId);
        setActiveTab("pages");
        setRecordType("");
        setRecordSearch("");
    };

    /**
     * Filter record logs by record type and search text.
     */
    const handleRecordSearch = async (event) => {
        event.preventDefault();

        if (!selectedRunId) return;

        try {
            setError("");
            await fetchRunDetails(selectedRunId);
        } catch (requestError) {
            setError(requestError.message);
        }
    };

    // Prefer the selected run details and fall back to the latest API run.
    const activeRun = dashboardData.active_run;

    const currentRun = runDetails ?? dashboardData.latest_run ?? null;

    const totalRecords = Number(
        pageLogs[0]?.total_records ?? currentRun?.total_fetched ?? 0,
    );

    const processedRecords = Number(currentRun?.total_fetched ?? 0);

    const progress = useMemo(() => {
        if (!currentRun) return 0;

        if (currentRun.status === "completed") return 100;

        if (totalRecords <= 0) return 0;

        return Math.min(
            99,
            Math.round((processedRecords / totalRecords) * 100),
        );
    }, [currentRun, processedRecords, totalRecords]);

    const isRunning = currentRun?.status === "running";

    return (
        <div className="admin-shell">
            <Sidebar adminName={adminName} />

            <main className="admin-main">
                <DashboardHeader adminName={adminName} />

                <div className="admin-content ls-content">
                    <section className="admin-page-heading ls-heading">
                        <div>
                            <p className="admin-eyebrow">
                                ADMIN MANAGEMENT <span>·</span> DATA OPERATIONS
                            </p>

                            <h1>Learner Synchronization</h1>

                            <p className="admin-page-heading__sub">
                                Monitor, manage and review YouthHub learner data
                                synchronization.
                            </p>
                        </div>

                        <button
                            type="button"
                            className="ls-refresh-btn"
                            onClick={handleRefresh}
                            disabled={loading}
                        >
                            <RefreshCw size={16} />
                            Refresh
                        </button>
                    </section>

                    {error && (
                        <div className="ls-alert ls-alert--error" role="alert">
                            <AlertCircle size={18} />
                            <span>{error}</span>
                            <button
                                type="button"
                                onClick={() => setError("")}
                                aria-label="Dismiss error"
                            >
                                <XCircle size={17} />
                            </button>
                        </div>
                    )}

                    {notice && (
                        <div
                            className="ls-alert ls-alert--success"
                            role="status"
                        >
                            <CheckCircle2 size={18} />
                            <span>{notice}</span>
                            <button
                                type="button"
                                onClick={() => setNotice("")}
                                aria-label="Dismiss message"
                            >
                                <XCircle size={17} />
                            </button>
                        </div>
                    )}

                    <section className="ls-control-panel">
                        <div className="ls-control-copy">
                            <div className="ls-control-icon">
                                <Database size={22} />
                            </div>

                            <div>
                                <h2>Synchronization Control</h2>
                                <p>
                                    Choose how you want to synchronize learner
                                    records.
                                </p>
                            </div>
                        </div>

                        <div className="ls-control-actions">
                            <button
                                type="button"
                                className="ls-btn ls-btn--secondary"
                                onClick={() => handleStartSync("incremental")}
                                disabled={starting || Boolean(activeRun)}
                            >
                                {starting ? (
                                    <RefreshCw size={17} className="ls-spin" />
                                ) : (
                                    <Activity size={17} />
                                )}
                                Incremental Sync
                            </button>

                            <button
                                type="button"
                                className="ls-btn ls-btn--primary"
                                onClick={() => handleStartSync("full")}
                                disabled={starting || Boolean(activeRun)}
                            >
                                <Play size={16} />
                                Full Sync
                            </button>
                        </div>

                        {activeRun && (
                            <div className="ls-running-note">
                                <span className="ls-running-pulse" />A
                                synchronization is currently running. New runs
                                are disabled.
                            </div>
                        )}
                    </section>

                    {loading ? (
                        <SyncSkeleton />
                    ) : (
                        <>
                            <section className="ls-metrics-grid">
                                <SyncMetric
                                    label="Total Fetched"
                                    value={currentRun?.total_fetched}
                                    icon={Database}
                                    tone="blue"
                                />

                                <SyncMetric
                                    label="Inserted"
                                    value={currentRun?.total_inserted}
                                    icon={CheckCircle2}
                                    tone="green"
                                />

                                <SyncMetric
                                    label="Updated"
                                    value={currentRun?.total_updated}
                                    icon={RefreshCw}
                                    tone="purple"
                                />

                                <SyncMetric
                                    label="Unchanged"
                                    value={currentRun?.total_unchanged}
                                    icon={ShieldAlert}
                                    tone="slate"
                                />

                                <SyncMetric
                                    label="Duplicates"
                                    value={currentRun?.total_duplicates}
                                    icon={Layers3}
                                    tone="amber"
                                />

                                <SyncMetric
                                    label="Failed"
                                    value={currentRun?.total_failed}
                                    icon={FileWarning}
                                    tone="red"
                                />
                            </section>

                            <section className="ls-progress-panel">
                                <div className="ls-section-heading">
                                    <div>
                                        <h2>Current Run Progress</h2>
                                        <p>
                                            {currentRun
                                                ? `Run started ${formatDate(currentRun.started_at)}`
                                                : "Select a synchronization run to view its progress."}
                                        </p>
                                    </div>

                                    {currentRun && (
                                        <SyncStatus
                                            status={currentRun.status}
                                        />
                                    )}
                                </div>

                                {currentRun ? (
                                    <>
                                        <div className="ls-progress-meta">
                                            <span>
                                                {formatNumber(processedRecords)}{" "}
                                                records fetched
                                                {totalRecords > 0
                                                    ? ` of ${formatNumber(totalRecords)}`
                                                    : ""}
                                            </span>

                                            <strong>{progress}%</strong>
                                        </div>

                                        <div
                                            className="ls-progress-track"
                                            role="progressbar"
                                            aria-valuenow={progress}
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        >
                                            <span
                                                style={{
                                                    width: `${progress}%`,
                                                }}
                                            />
                                        </div>

                                        <div className="ls-progress-footer">
                                            <span>
                                                <Clock3 size={14} />
                                                {currentRun.duration_seconds
                                                    ? `${formatNumber(currentRun.duration_seconds)} seconds`
                                                    : "Duration updating"}
                                            </span>

                                            <span>
                                                {formatNumber(
                                                    currentRun.total_pages,
                                                )}{" "}
                                                pages processed
                                            </span>

                                            <span>
                                                {currentRun.completed_at
                                                    ? `Completed ${formatDate(currentRun.completed_at)}`
                                                    : isRunning
                                                      ? "Synchronization in progress"
                                                      : "Waiting for completion"}
                                            </span>
                                        </div>

                                        {currentRun.error_message && (
                                            <div className="ls-run-error">
                                                <AlertCircle size={16} />
                                                {currentRun.error_message}
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <div className="ls-empty-state">
                                        <Activity size={28} />
                                        <strong>
                                            No synchronization run selected
                                        </strong>
                                        <span>
                                            Start a sync or choose a run from
                                            recent history.
                                        </span>
                                    </div>
                                )}
                            </section>

                            <section className="ls-history-panel">
                                <div className="ls-section-heading">
                                    <div>
                                        <h2>
                                            <History size={19} />
                                            Synchronization Details
                                        </h2>
                                        <p>
                                            Review page activity and duplicate
                                            or failed records.
                                        </p>
                                    </div>

                                    <div className="ls-tabs">
                                        <button
                                            type="button"
                                            className={
                                                activeTab === "pages"
                                                    ? "active"
                                                    : ""
                                            }
                                            onClick={() =>
                                                setActiveTab("pages")
                                            }
                                        >
                                            Page Logs
                                        </button>

                                        <button
                                            type="button"
                                            className={
                                                activeTab === "records"
                                                    ? "active"
                                                    : ""
                                            }
                                            onClick={() =>
                                                setActiveTab("records")
                                            }
                                        >
                                            Record Issues
                                        </button>
                                    </div>
                                </div>

                                {activeTab === "pages" ? (
                                    <div className="ls-table-wrap">
                                        <table className="ls-table">
                                            <thead>
                                                <tr>
                                                    <th>Page</th>
                                                    <th>Fetched</th>
                                                    <th>Total Records</th>
                                                    <th>Remaining</th>
                                                    <th>Status</th>
                                                    <th>Fetched At</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                {pageLogs.length > 0 ? (
                                                    pageLogs.map((page) => (
                                                        <tr key={page.id}>
                                                            <td>
                                                                #
                                                                {
                                                                    page.page_number
                                                                }
                                                            </td>
                                                            <td>
                                                                {formatNumber(
                                                                    page.records_fetched,
                                                                )}
                                                            </td>
                                                            <td>
                                                                {formatNumber(
                                                                    page.total_records,
                                                                )}
                                                            </td>
                                                            <td>
                                                                {formatNumber(
                                                                    page.records_remaining,
                                                                )}
                                                            </td>
                                                            <td>
                                                                <SyncStatus
                                                                    status={
                                                                        page.status
                                                                    }
                                                                />
                                                            </td>
                                                            <td>
                                                                {formatDate(
                                                                    page.fetched_at,
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))
                                                ) : (
                                                    <tr>
                                                        <td
                                                            colSpan="6"
                                                            className="ls-table-empty"
                                                        >
                                                            No page logs
                                                            available for this
                                                            run.
                                                        </td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <>
                                        <form
                                            className="ls-record-filters"
                                            onSubmit={handleRecordSearch}
                                        >
                                            <select
                                                value={recordType}
                                                onChange={(event) =>
                                                    setRecordType(
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    All record issues
                                                </option>
                                                <option value="duplicate">
                                                    Duplicate
                                                </option>
                                                <option value="ambiguous">
                                                    Ambiguous
                                                </option>
                                                <option value="failed">
                                                    Failed
                                                </option>
                                            </select>

                                            <div className="ls-search-box">
                                                <Search size={16} />
                                                <input
                                                    type="search"
                                                    value={recordSearch}
                                                    onChange={(event) =>
                                                        setRecordSearch(
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="Search mobile number or reason..."
                                                />
                                            </div>

                                            <button
                                                type="submit"
                                                className="ls-filter-btn"
                                            >
                                                Apply
                                            </button>
                                        </form>

                                        <div className="ls-table-wrap">
                                            <table className="ls-table">
                                                <thead>
                                                    <tr>
                                                        <th>Page</th>
                                                        <th>Type</th>
                                                        <th>Phone Number</th>
                                                        <th>Reason</th>
                                                        <th>Logged At</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    {recordLogs.length > 0 ? (
                                                        recordLogs.map(
                                                            (record) => (
                                                                <tr
                                                                    key={
                                                                        record.id
                                                                    }
                                                                >
                                                                    <td>
                                                                        #
                                                                        {
                                                                            record.page_number
                                                                        }
                                                                    </td>
                                                                    <td>
                                                                        <span
                                                                            className={`ls-record-type ls-record-type--${record.record_type}`}
                                                                        >
                                                                            {
                                                                                record.record_type
                                                                            }
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        {record.phone_number ||
                                                                            record.normalized_mobile ||
                                                                            "—"}
                                                                    </td>
                                                                    <td className="ls-reason-cell">
                                                                        {record.reason ||
                                                                            "—"}
                                                                    </td>
                                                                    <td>
                                                                        {formatDate(
                                                                            record.created_at,
                                                                        )}
                                                                    </td>
                                                                </tr>
                                                            ),
                                                        )
                                                    ) : (
                                                        <tr>
                                                            <td
                                                                colSpan="5"
                                                                className="ls-table-empty"
                                                            >
                                                                No matching
                                                                record issues
                                                                found.
                                                            </td>
                                                        </tr>
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </>
                                )}
                            </section>

                            <section className="ls-recent-panel">
                                <div className="ls-section-heading">
                                    <div>
                                        <h2>Recent Synchronization Runs</h2>
                                        <p>
                                            Select a run to inspect its progress
                                            and logs.
                                        </p>
                                    </div>
                                </div>

                                <div className="ls-table-wrap">
                                    <table className="ls-table">
                                        <thead>
                                            <tr>
                                                <th>Run ID</th>
                                                <th>Type</th>
                                                <th>Started At</th>
                                                <th>Fetched</th>
                                                <th>Inserted</th>
                                                <th>Updated</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            {dashboardData.recent_runs.length >
                                            0 ? (
                                                dashboardData.recent_runs.map(
                                                    (run) => (
                                                        <tr
                                                            key={run.id}
                                                            className={
                                                                selectedRunId ===
                                                                run.id
                                                                    ? "ls-row-selected"
                                                                    : ""
                                                            }
                                                        >
                                                            <td className="ls-run-id">
                                                                {String(
                                                                    run.run_id ??
                                                                        "",
                                                                ).slice(0, 8) ||
                                                                    `#${run.id}`}
                                                            </td>
                                                            <td>
                                                                <span className="ls-type-label">
                                                                    {run.sync_type ||
                                                                        "—"}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                {formatDate(
                                                                    run.started_at,
                                                                )}
                                                            </td>
                                                            <td>
                                                                {formatNumber(
                                                                    run.total_fetched,
                                                                )}
                                                            </td>
                                                            <td>
                                                                {formatNumber(
                                                                    run.total_inserted,
                                                                )}
                                                            </td>
                                                            <td>
                                                                {formatNumber(
                                                                    run.total_updated,
                                                                )}
                                                            </td>
                                                            <td>
                                                                <SyncStatus
                                                                    status={
                                                                        run.status
                                                                    }
                                                                />
                                                            </td>
                                                            <td>
                                                                <button
                                                                    type="button"
                                                                    className="ls-view-btn"
                                                                    onClick={() =>
                                                                        handleSelectRun(
                                                                            run.id,
                                                                        )
                                                                    }
                                                                >
                                                                    View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <tr>
                                                    <td
                                                        colSpan="8"
                                                        className="ls-table-empty"
                                                    >
                                                        No synchronization
                                                        history available yet.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </>
                    )}

                    <footer className="admin-footer">
                        <span>© 2026 YouthHub Partner</span>
                        <span>Learner Synchronization</span>
                    </footer>
                </div>
            </main>
        </div>
    );
}
