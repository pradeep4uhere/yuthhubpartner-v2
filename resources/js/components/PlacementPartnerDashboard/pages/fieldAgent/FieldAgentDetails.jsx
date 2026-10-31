import { useCallback, useEffect, useMemo, useState } from "react";

import {
    ArrowLeft,
    Search,
    RefreshCw,
    ChevronLeft,
    ChevronRight,
    FileText,
    Users,
    Files,
    AlertCircle,
    CircleCheck,
    CircleX,
    Send,
    SlidersHorizontal,
    Plus,
    Minus,
    Download,
} from "lucide-react";

import "./FieldAgentDetails.css";

import EventTransactionDrawer from "../event/EventTransactionDrawer";
import useEventTransactions from "../event/shared/useEventTransactions.js";

/* =========================================
   CONFIGURATION
========================================= */

const COLUMNS = [
    ["id", "Event ID"],
    ["agent_id", "Agent ID"],
    ["unit_institute", "Unit Institute"],
    ["event_name", "Event Name"],
    ["event_category", "Event Category"],
    ["beneficiary_name", "Beneficiary Name"],
    ["beneficiary_number", "Beneficiary Number"],
    ["event_status", "Event Status"],
    ["event_submitted", "Event Submitted"],
    ["documents", "Documents"],
    ["differently_abled", "Differently Abled"],
    ["education_level", "Education Level"],
    ["course_name", "Course Name"],
    ["marital_status", "Marital Status"],
    ["religion", "Religion"],
    ["digital_proficiency_level", "Digital Proficiency Level"],
    ["english_proficiency_level", "English Proficiency Level"],
    ["interested_opportunities", "Interested Opportunities"],
    ["function_type", "Function Type"],
    ["industry_type", "Industry Type"],
    ["age_group", "Age Group"],
    ["state", "State"],
    ["district", "District"],
    ["comment", "Comment"],
    ["updated", "Updated"],
];

const SUMMARY_CARDS = [
    {
        label: "Total Transactions",
        key: "total",
        icon: Files,
        variant: "total",
        description: "All submitted & processed",
    },
    {
        label: "Submitted",
        key: "submitted",
        icon: Send,
        variant: "submitted",
        description: "Awaiting review",
    },
    {
        label: "Action Required",
        key: "action_required",
        icon: AlertCircle,
        variant: "required",
        description: "Needs attention",
    },
    {
        label: "Accepted",
        key: "accepted",
        icon: CircleCheck,
        variant: "accepted",
        description: "Successfully accepted",
    },
    {
        label: "Rejected",
        key: "rejected",
        icon: CircleX,
        variant: "rejected",
        description: "Rejected transactions",
    },
];

const EMPTY_ADVANCED_FILTERS = {
    date_from: "",
    date_to: "",
    function_type: "",
    industry_type: "",
    state: "",
    district: "",
};

const ADVANCED_TEXT_FIELDS = [
    ["function_type", "Function Type"],
    ["industry_type", "Industry Type"],
    ["state", "State"],
    ["district", "District"],
];

/* =========================================
   HELPERS
========================================= */

/**
 * Formats empty transaction values consistently.
 */
const fmt = (value) => {
    if (value === null || value === undefined || value === "") {
        return "—";
    }

    return String(value);
};

/**
 * Formats database dates without shifting date-only values across timezones.
 */
const formatDate = (value) => {
    if (!value) return "—";

    const raw = String(value);
    const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (match) {
        const [, year, month, day] = match;

        const parsed = new Date(
            Date.UTC(Number(year), Number(month) - 1, Number(day)),
        );

        if (Number.isNaN(parsed.getTime())) return "—";

        return parsed.toLocaleDateString("en-IN", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            timeZone: "UTC",
        });
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "—";

    return parsed.toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

/**
 * Formats numeric values using the Indian numbering system.
 */
const formatNumber = (value) => {
    return Number(value ?? 0).toLocaleString("en-IN");
};

/**
 * Checks whether any applied Advanced Search field has a value.
 */
const hasAppliedAdvancedFilters = (filters) => {
    return Object.values(filters).some(
        (value) => String(value ?? "").trim() !== "",
    );
};

/**
 * Displays a lightweight loading placeholder.
 */
const Skeleton = ({ width = "100%" }) => (
    <span className="fad-skeleton" style={{ width }} aria-hidden="true" />
);

/* =========================================
   MAIN COMPONENT
========================================= */

/**
 * Displays the selected Field Agent profile and associated transactions.
 * Uses the shared transaction hook for server-side filtering and pagination.
 */
export default function FieldAgentDetails({
    selectedAgentId,
    agentId: providedAgentId = null,
    onBack,
}) {
    const agentId = selectedAgentId ?? providedAgentId;

    /* =====================================
       LOCAL UI STATE
    ===================================== */

    const [search, setSearch] = useState("");
    const [debounced, setDebounced] = useState("");

    const [status, setStatus] = useState("");

    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(25);

    const [reload, setReload] = useState(0);

    const [hidden, setHidden] = useState([]);
    const [showColumns, setShowColumns] = useState(false);

    const [selectedTransactionId, setSelectedTransactionId] = useState(null);

    const [showAdvanced, setShowAdvanced] = useState(false);

    const [advancedDraft, setAdvancedDraft] = useState({
        ...EMPTY_ADVANCED_FILTERS,
    });

    const [advancedFilters, setAdvancedFilters] = useState({
        ...EMPTY_ADVANCED_FILTERS,
    });

    // Tracks the backend Excel download request.
    const [exporting, setExporting] = useState(false);

    // Stores any error returned during the export request.
    const [exportError, setExportError] = useState("");

    /* =====================================
       DEBOUNCED SEARCH
    ===================================== */

    /**
     * Delays main search requests to avoid fetching on every keystroke.
     */
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebounced(search.trim());
        }, 350);

        return () => clearTimeout(timer);
    }, [search]);

    /* =====================================
       RESET WHEN AGENT CHANGES
    ===================================== */

    /**
     * Resets local search, filters and pagination when another agent is selected.
     */
    useEffect(() => {
        setSearch("");
        setDebounced("");
        setStatus("");

        setAdvancedDraft({ ...EMPTY_ADVANCED_FILTERS });
        setAdvancedFilters({ ...EMPTY_ADVANCED_FILTERS });

        setShowAdvanced(false);
        setShowColumns(false);

        setPage(1);
        setSelectedTransactionId(null);
    }, [agentId]);

    /* =====================================
       SHARED TRANSACTION DATA
    ===================================== */

    const {
        transactions,
        agent,
        summary,
        pagination,
        loading,
        initialLoading,
        error,
    } = useEventTransactions({
        agentId,
        page,
        perPage,
        search: debounced,
        status,
        advancedFilters,
        refreshKey: reload,
    });

    const records = transactions ?? [];

    const currentPage = Number(pagination?.current_page ?? page);

    const totalPages = Math.max(1, Number(pagination?.last_page ?? 1));

    const totalRecords = Number(pagination?.total ?? 0);

    const recordsPerPage = Number(pagination?.per_page ?? perPage);

    /* =====================================
       SEARCH RESULT VISIBILITY
    ===================================== */

    const hasActiveSearch =
        debounced.trim() !== "" ||
        status !== "" ||
        hasAppliedAdvancedFilters(advancedFilters);

    const isSearchPending = search.trim() !== debounced || loading;

    const hasSearchResults = !isSearchPending && !error && totalRecords > 0;

    /**
     * Downloads the complete filtered transaction Excel file generated by Laravel.
     */
    const exportFilteredTransactions = useCallback(async () => {
        if (
            !agentId ||
            !hasActiveSearch ||
            !hasSearchResults ||
            loading ||
            exporting ||
            error
        ) {
            return;
        }

        setExporting(true);
        setExportError("");

        try {
            // Send only the currently applied filters to the backend export endpoint.
            const params = new URLSearchParams();

            if (debounced.trim()) {
                params.set("search", debounced.trim());
            }

            if (status) {
                params.set("status", status);
            }

            Object.entries(advancedFilters).forEach(([key, value]) => {
                if (String(value ?? "").trim() !== "") {
                    params.set(key, String(value).trim());
                }
            });

            // Request the Excel file directly from Laravel, not from frontend table rows.
            const response = await fetch(
                `/api/partner-placement/field-agents/${encodeURIComponent(agentId)}/transactions/export?${params.toString()}`,
                {
                    method: "GET",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json",
                    },
                },
            );

            // Convert backend errors into readable messages before processing the file.
            const contentType = response.headers.get("content-type") || "";

            if (!response.ok || contentType.includes("application/json")) {
                const responseText = await response.text();

                let message = "Unable to export transactions.";

                try {
                    const payload = JSON.parse(responseText);
                    message = payload.message || message;
                } catch {
                    if (responseText.trim()) {
                        message = responseText;
                    }
                }

                throw new Error(message);
            }

            // Receive the complete backend-generated Excel file as binary data.
            const blob = await response.blob();

            if (!blob.size) {
                throw new Error("The exported Excel file is empty.");
            }

            // Prefer the filename provided by Laravel, with a safe fallback.
            const disposition =
                response.headers.get("content-disposition") || "";
            const filenameMatch = disposition.match(/filename="?([^";]+)"?/i);

            const safeAgentName = String(agent?.name || "FieldAgent")
                .replace(/[^a-z0-9_-]/gi, "_")
                .slice(0, 80);

            const filename =
                filenameMatch?.[1] ||
                `FieldAgent_Transactions_${safeAgentName}_${new Date()
                    .toISOString()
                    .slice(0, 10)}.xlsx`;

            // Trigger a browser download without converting frontend records into Excel.
            const fileUrl = window.URL.createObjectURL(blob);
            const link = document.createElement("a");

            link.href = fileUrl;
            link.download = filename;

            document.body.appendChild(link);
            link.click();
            link.remove();

            window.URL.revokeObjectURL(fileUrl);
        } catch (exportException) {
            setExportError(
                exportException?.message || "Unable to export transactions.",
            );
        } finally {
            setExporting(false);
        }
    }, [
        agentId,
        hasActiveSearch,
        hasSearchResults,
        loading,
        exporting,
        error,
        debounced,
        status,
        advancedFilters,
        agent,
    ]);

    /* =====================================
       COLUMN VISIBILITY
    ===================================== */

    const visible = useMemo(
        () => COLUMNS.filter(([key]) => !hidden.includes(key)),
        [hidden],
    );

    /* =====================================
       EVENT HANDLERS
    ===================================== */

    /**
     * Opens the selected Event Transaction in the existing detail drawer.
     */
    const openTransaction = useCallback((id) => {
        if (id === null || id === undefined) return;

        setSelectedTransactionId(id);
    }, []);

    /**
     * Closes the Event Transaction detail drawer.
     */
    const closeTransaction = useCallback(() => {
        setSelectedTransactionId(null);
    }, []);

    /**
     * Navigates back to the associated Field Agents listing.
     */
    const handleBack = useCallback(() => {
        if (typeof onBack === "function") {
            onBack();
            return;
        }

        window.history.back();
    }, [onBack]);

    /**
     * Updates the main search input and resets pagination.
     */
    const updateSearch = useCallback((value) => {
        setSearch(value);
        setPage(1);
    }, []);

    /**
     * Updates the selected status and resets pagination.
     */
    const updateStatus = useCallback((value) => {
        setStatus(value);
        setPage(1);
    }, []);

    /**
     * Reloads the current transaction listing.
     */
    const refreshTransactions = useCallback(() => {
        setReload((previous) => previous + 1);
    }, []);

    /**
     * Shows or hides a selected transaction table column.
     */
    const toggleColumn = useCallback((key) => {
        setHidden((previous) =>
            previous.includes(key)
                ? previous.filter((item) => item !== key)
                : [...previous, key],
        );
    }, []);

    /**
     * Restores visibility for every transaction table column.
     */
    const showAllColumns = useCallback(() => {
        setHidden([]);
    }, []);

    /**
     * Updates the number of records displayed per page.
     */
    const updatePerPage = useCallback((value) => {
        setPerPage(Number(value));
        setPage(1);
    }, []);

    /**
     * Navigates to the previous transaction page.
     */
    const previousPage = useCallback(() => {
        setPage((previous) => Math.max(1, previous - 1));
    }, []);

    /**
     * Navigates to the next transaction page.
     */
    const nextPage = useCallback(() => {
        setPage((previous) => Math.min(totalPages, previous + 1));
    }, [totalPages]);

    /**
     * Updates an individual Advanced Search draft field.
     */
    const updateAdvancedFilter = useCallback((key, value) => {
        setAdvancedDraft((previous) => ({
            ...previous,
            [key]: value,
        }));
    }, []);

    /**
     * Applies Advanced Search filters and resets pagination.
     */
    const applyAdvancedFilters = useCallback(() => {
        if (
            advancedDraft.date_from &&
            advancedDraft.date_to &&
            advancedDraft.date_from > advancedDraft.date_to
        ) {
            return;
        }

        setAdvancedFilters({ ...advancedDraft });
        setPage(1);
    }, [advancedDraft]);

    /**
     * Clears Advanced Search filters and resets pagination.
     */
    const clearAdvancedFilters = useCallback(() => {
        setAdvancedDraft({ ...EMPTY_ADVANCED_FILTERS });
        setAdvancedFilters({ ...EMPTY_ADVANCED_FILTERS });
        setPage(1);
    }, []);

    /* =====================================
       PAGINATION CALCULATIONS
    ===================================== */

    const start = totalRecords ? (currentPage - 1) * recordsPerPage + 1 : 0;

    const end = totalRecords
        ? Math.min(totalRecords, start + records.length - 1)
        : 0;

    /* =====================================
       TABLE CELL RENDERER
    ===================================== */

    /**
     * Renders a transaction cell according to its column definition.
     */
    const renderCell = (row, key) => {
        if (key === "id") {
            return (
                <button
                    type="button"
                    className="fad-event-id-link"
                    onClick={() => openTransaction(row.id)}
                    title="View Event Transaction"
                >
                    {row.eventId ?? row.id}
                </button>
            );
        }

        if (key === "documents") {
            const documents = Array.isArray(row.documents)
                ? row.documents.filter((doc) => doc?.url)
                : [];

            return documents.length ? (
                <div className="fad-docs">
                    {documents.map((doc, index) => (
                        <a
                            key={`${row.id}-${index}`}
                            href={doc.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            title={doc.label || `Document ${index + 1}`}
                        >
                            <FileText size={14} />
                            {index + 1}
                        </a>
                    ))}
                </div>
            ) : (
                "—"
            );
        }

        if (key === "event_status") {
            const normalized = String(row[key] ?? "")
                .toLowerCase()
                .replace(/[^a-z_]/g, "");

            return (
                <span className={`fad-status ${normalized}`}>
                    {fmt(row[key])}
                </span>
            );
        }

        if (key === "event_submitted" || key === "updated") {
            return formatDate(row[key]);
        }

        return (
            <span title={fmt(row[key])} className="fad-truncate">
                {fmt(row[key])}
            </span>
        );
    };

    /* =====================================
       RENDER
    ===================================== */

    return (
        <section className="fad-page">
            {/* PAGE HEADER */}

            <div className="fad-heading">
                <button
                    type="button"
                    className="fad-back-btn"
                    onClick={handleBack}
                >
                    <ArrowLeft size={16} />
                    Back to Field Agents
                </button>

                <div>
                    <small>YOUTHHUB PARTNER / FIELD AGENTS</small>

                    <h2>Field Agent Transactions</h2>

                    <p>
                        Complete event transaction history for the selected
                        field agent.
                    </p>
                </div>

                <button
                    type="button"
                    className="fad-refresh"
                    onClick={refreshTransactions}
                    disabled={loading || !agentId}
                >
                    <RefreshCw size={16} className={loading ? "pl-spin" : ""} />
                    Refresh
                </button>
            </div>

            {/* INVALID AGENT */}

            {!agentId && (
                <div className="fad-error" role="alert">
                    <AlertCircle size={17} />
                    Invalid Field Agent ID.
                </div>
            )}

            {/* FIELD AGENT PROFILE */}

            <div className="fad-agent">
                {initialLoading && loading ? (
                    <>
                        <Skeleton width="180px" />
                        <Skeleton width="260px" />
                    </>
                ) : agent ? (
                    <>
                        <div className="fad-avatar">
                            <Users size={20} />
                        </div>

                        <div>
                            <strong>{agent.name || "Field Agent"}</strong>

                            <small>
                                {agent.agentId ?? "—"}
                                {" · "}
                                {agent.mobile ?? "—"}

                                {agent.center ? ` · ${agent.center}` : ""}
                            </small>
                        </div>
                    </>
                ) : (
                    <div>
                        <strong>Field Agent Details</strong>

                        <small>
                            {loading
                                ? "Loading agent profile..."
                                : error
                                  ? "Unable to load profile."
                                  : "No agent information available."}
                        </small>
                    </div>
                )}
            </div>

            {/* SUMMARY CARDS */}

            <div className="fad-stats">
                {SUMMARY_CARDS.map(
                    ({ label, key, icon: Icon, variant, description }) => (
                        <div
                            className={`fad-stat fad-stat--${variant}`}
                            key={key}
                        >
                            <div className="fad-stat-icon">
                                <Icon size={20} strokeWidth={2.2} />
                            </div>

                            <div className="fad-stat-content">
                                <span className="fad-stat-title">{label}</span>

                                {initialLoading && loading ? (
                                    <Skeleton width="55px" />
                                ) : (
                                    <strong className="fad-stat-value">
                                        {formatNumber(summary?.[key] ?? 0)}
                                    </strong>
                                )}

                                <span className="fad-stat-description">
                                    {description}
                                </span>
                            </div>

                            <span className="fad-stat-arrow" aria-hidden="true">
                                <ChevronRight size={15} />
                            </span>
                        </div>
                    ),
                )}
            </div>

            {/* TRANSACTION PANEL */}

            <div className="fad-panel">
                <div className="fad-search-layout">
                    {/* MAIN SEARCH TOOLBAR */}

                    <div className="fad-toolbar">
                        {/* MAIN SEARCH INPUT */}

                        <label className="fad-search">
                            <Search size={17} />

                            <input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    updateSearch(event.target.value)
                                }
                                placeholder="Search Event ID, name, beneficiary, mobile..."
                            />
                        </label>

                        {/* STATUS FILTER */}

                        <select
                            value={status}
                            onChange={(event) =>
                                updateStatus(event.target.value)
                            }
                            aria-label="Filter transaction status"
                        >
                            <option value="">All Statuses</option>

                            <option value="submitted">Submitted</option>

                            <option value="action_required">
                                Action Required
                            </option>

                            <option value="accepted">Accepted</option>

                            <option value="rejected">Rejected</option>
                        </select>

                        {/* ADVANCED SEARCH BUTTON */}

                        <button
                            type="button"
                            className="fad-advanced-toggle"
                            onClick={() =>
                                setShowAdvanced((previous) => !previous)
                            }
                            aria-expanded={showAdvanced}
                        >
                            <SlidersHorizontal size={17} strokeWidth={2} />

                            <span>Advanced Search</span>

                            {showAdvanced ? (
                                <Minus size={16} strokeWidth={2.5} />
                            ) : (
                                <Plus size={16} strokeWidth={2.5} />
                            )}
                        </button>

                        {/* COLUMN SELECTOR */}

                        <div className="fad-columns">
                            <button
                                type="button"
                                onClick={() =>
                                    setShowColumns((previous) => !previous)
                                }
                                aria-expanded={showColumns}
                            >
                                Columns ({visible.length}/{COLUMNS.length})
                            </button>

                            {showColumns && (
                                <div className="fad-column-menu">
                                    <button
                                        type="button"
                                        onClick={showAllColumns}
                                    >
                                        Show all
                                    </button>

                                    {COLUMNS.map(([key, label]) => (
                                        <label key={key}>
                                            <input
                                                type="checkbox"
                                                checked={!hidden.includes(key)}
                                                onChange={() =>
                                                    toggleColumn(key)
                                                }
                                            />

                                            {label}
                                        </label>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* ADVANCED SEARCH PANEL */}

                    {showAdvanced && (
                        <div className="fad-advanced-panel">
                            {/* ADVANCED FILTER INPUTS */}

                            <div className="fad-advanced-grid">
                                {/* DATE FROM */}

                                <label>
                                    <span>Date From</span>

                                    <input
                                        type="date"
                                        value={advancedDraft.date_from}
                                        max={advancedDraft.date_to || undefined}
                                        onChange={(event) =>
                                            updateAdvancedFilter(
                                                "date_from",
                                                event.target.value,
                                            )
                                        }
                                    />
                                </label>

                                {/* DATE TO */}

                                <label>
                                    <span>Date To</span>

                                    <input
                                        type="date"
                                        value={advancedDraft.date_to}
                                        min={
                                            advancedDraft.date_from || undefined
                                        }
                                        onChange={(event) =>
                                            updateAdvancedFilter(
                                                "date_to",
                                                event.target.value,
                                            )
                                        }
                                    />
                                </label>

                                {/* FUNCTION / INDUSTRY / STATE / DISTRICT */}

                                {ADVANCED_TEXT_FIELDS.map(([key, label]) => (
                                    <label key={key}>
                                        <span>{label}</span>

                                        <input
                                            type="search"
                                            value={advancedDraft[key]}
                                            placeholder={`Search ${label}`}
                                            onChange={(event) =>
                                                updateAdvancedFilter(
                                                    key,
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                ))}
                            </div>

                            {/* SINGLE ADVANCED SEARCH FOOTER */}

                            <div className="fad-advanced-actions">
                                {/* LEFT: SEARCH RESULT */}

                                <div className="fad-advanced-result">
                                    {hasActiveSearch && (
                                        <div
                                            className={`fad-stat fad-search-result ${
                                                hasSearchResults
                                                    ? "fad-stat--accepted"
                                                    : "fad-stat--rejected"
                                            }`}
                                            role="status"
                                            aria-live="polite"
                                        >
                                            <span className="fad-stat-icon">
                                                {hasSearchResults ? (
                                                    <CircleCheck size={15} />
                                                ) : (
                                                    <CircleX size={15} />
                                                )}
                                            </span>

                                            <span className="fad-stat-title">
                                                Search Result:
                                            </span>

                                            <strong className="fad-stat-value">
                                                {isSearchPending
                                                    ? "..."
                                                    : error
                                                      ? "—"
                                                      : formatNumber(
                                                            totalRecords,
                                                        )}
                                            </strong>

                                            <span className="fad-stat-description">
                                                {isSearchPending
                                                    ? "Searching..."
                                                    : error
                                                      ? "Unable to load"
                                                      : totalRecords > 0
                                                        ? "Matching transactions found"
                                                        : "No matching records"}
                                            </span>
                                        </div>
                                    )}
                                </div>

                                {/* RIGHT: ACTION BUTTONS */}

                                <div className="fad-advanced-buttons">
                                    <button
                                        type="button"
                                        className="fad-clear-btn"
                                        onClick={clearAdvancedFilters}
                                    >
                                        Clear Filters
                                    </button>

                                    <button
                                        type="button"
                                        className="fad-apply-btn"
                                        onClick={applyAdvancedFilters}
                                        disabled={Boolean(
                                            advancedDraft.date_from &&
                                            advancedDraft.date_to &&
                                            advancedDraft.date_from >
                                                advancedDraft.date_to,
                                        )}
                                    >
                                        <Search size={15} />
                                        Apply Filters
                                    </button>

                                    {/* DOWNLOAD THE BACKEND-GENERATED EXCEL FILE */}
                                    <button
                                        type="button"
                                        className="fad-export-btn"
                                        onClick={exportFilteredTransactions}
                                        disabled={
                                            !hasActiveSearch ||
                                            !hasSearchResults ||
                                            loading ||
                                            exporting ||
                                            Boolean(error)
                                        }
                                        title={
                                            !hasActiveSearch
                                                ? "Apply a search or filter before exporting"
                                                : "Export all matching transactions"
                                        }
                                    >
                                        {exporting ? (
                                            <RefreshCw
                                                size={15}
                                                className="pl-spin"
                                            />
                                        ) : (
                                            <Download size={15} />
                                        )}
                                        {exporting
                                            ? "Exporting..."
                                            : "Export Excel"}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                    {/* EXPORT ERROR MESSAGE */}
                    {exportError && (
                        <div className="fad-error" role="alert">
                            <AlertCircle size={17} />
                            <span>{exportError}</span>
                        </div>
                    )}
                </div>

                {/* ERROR MESSAGE */}

                {error && (
                    <div className="fad-error" role="alert">
                        <AlertCircle size={17} />

                        <span>{error}</span>

                        <button
                            type="button"
                            onClick={refreshTransactions}
                            disabled={loading}
                        >
                            Retry
                        </button>
                    </div>
                )}

                {/* TRANSACTION TABLE */}

                <div className="fad-table-scroll" aria-busy={loading}>
                    <table className="fad-table">
                        <thead>
                            <tr>
                                {visible.map(([key, label]) => (
                                    <th key={key}>{label}</th>
                                ))}
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                Array.from(
                                    {
                                        length: Math.min(perPage, 8),
                                    },
                                    (_, index) => (
                                        <tr key={index}>
                                            {visible.map(([key]) => (
                                                <td key={key}>
                                                    <Skeleton
                                                        width={
                                                            key === "comment"
                                                                ? "140px"
                                                                : "80px"
                                                        }
                                                    />
                                                </td>
                                            ))}
                                        </tr>
                                    ),
                                )
                            ) : error ? (
                                <tr>
                                    <td
                                        colSpan={Math.max(visible.length, 1)}
                                        className="fad-empty"
                                    >
                                        Unable to load transactions.
                                    </td>
                                </tr>
                            ) : records.length > 0 ? (
                                records.map((row) => (
                                    <tr key={row.id}>
                                        {visible.map(([key]) => (
                                            <td key={key}>
                                                {renderCell(row, key)}
                                            </td>
                                        ))}
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={Math.max(visible.length, 1)}
                                        className="fad-empty"
                                    >
                                        No transactions found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* PAGINATION */}

                <div className="fad-footer">
                    <span>
                        {loading
                            ? "Loading transactions..."
                            : `Showing ${start}–${end} of ${formatNumber(totalRecords)} results`}
                    </span>

                    <div>
                        <label>
                            Rows per page{" "}
                            <select
                                value={perPage}
                                onChange={(event) =>
                                    updatePerPage(event.target.value)
                                }
                                disabled={loading}
                            >
                                {[10, 25, 50, 100].map((number) => (
                                    <option key={number} value={number}>
                                        {number}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <button
                            type="button"
                            disabled={loading || currentPage <= 1}
                            onClick={previousPage}
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>

                        <span>
                            {currentPage} / {totalPages}
                        </span>

                        <button
                            type="button"
                            disabled={loading || currentPage >= totalPages}
                            onClick={nextPage}
                            aria-label="Next page"
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </div>
            </div>

            {/* EVENT TRANSACTION DRAWER */}

            {selectedTransactionId !== null && (
                <EventTransactionDrawer
                    transactionId={selectedTransactionId}
                    onClose={closeTransaction}
                />
            )}
        </section>
    );
}
