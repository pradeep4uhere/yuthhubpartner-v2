import { useCallback, useEffect, useMemo, useState } from "react";
import {
    Search,
    Download,
    SlidersHorizontal,
    ChevronLeft,
    ChevronRight,
    RefreshCw,
    CalendarDays,
    X,
    ReceiptText,
    CheckCircle2,
    Clock3,
    IndianRupee,
} from "lucide-react";

import "./EventTransactions.css";
import "../../../../partner/pages/PartnerLearnerList.css";
import EventTransactionDrawer from "./EventTransactionDrawer";
const API_BASE = "/api/partner-placement/event-transactions";
const PAGE_SIZE = 10;

const EMPTY_FILTERS = {
    beneficiary_name: "",
    beneficiary_phone_number: "",
    event_type_name: "",
    event_category_name: "",
    event_master_type_id: "",
    review_status: "",
    workflow_status: "",
    date_from: "",
    date_to: "",
};

const FILTER_FIELDS = [
    {
        key: "beneficiary_name",
        label: "Beneficiary Name",
        placeholder: "Enter beneficiary name",
    },
    {
        key: "beneficiary_phone_number",
        label: "Mobile Number",
        placeholder: "Enter mobile number",
    },
    {
        key: "event_type_name",
        label: "Event Type",
        placeholder: "Enter event type",
    },
    {
        key: "event_category_name",
        label: "Event Category",
        placeholder: "Enter event category",
    },
    {
        key: "event_master_type_id",
        label: "Event Master Type ID",
        placeholder: "Enter type ID",
    },
];

const formatNumber = (value) => Number(value ?? 0).toLocaleString("en-IN");

const formatAmount = (value) =>
    Number(value ?? 0).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2,
    });

const formatDate = (value) => {
    if (!value) return "—";

    // Handle MySQL date strings without timezone shifts.
    const datePart = String(value).slice(0, 10);
    const match = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);

    if (!match) return "—";

    const [, year, month, day] = match;

    return `${day} ${new Date(
        Date.UTC(Number(year), Number(month) - 1, Number(day)),
    ).toLocaleString("en-IN", {
        month: "short",
        timeZone: "UTC",
    })} ${year}`;
};

const initials = (name = "") =>
    String(name || "")
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0] || "")
        .join("")
        .toUpperCase() || "B";

/* =========================================
   SKELETON COMPONENTS
========================================= */

const Skeleton = ({ width, height, circle = false, className = "" }) => (
    <span
        className={`pl-skeleton ${
            circle ? "pl-skeleton--circle" : ""
        } ${className}`}
        style={{ width, height }}
        aria-hidden="true"
    />
);

const SummarySkeleton = ({ color }) => (
    <article className={`pl-summary-card pl-summary-card--${color}`}>
        <Skeleton width={42} height={42} circle />

        <div className="pl-summary-content">
            <Skeleton width={105} height={11} />
            <Skeleton width={85} height={25} />
            <Skeleton width={135} height={10} />
        </div>

        <Skeleton width={24} height={24} circle />
    </article>
);

const TransactionTableSkeleton = ({ rows = PAGE_SIZE }) => (
    <>
        {Array.from({ length: rows }, (_, index) => (
            <tr className="pl-skeleton-row" key={index}>
                <td>
                    <Skeleton width={55} height={12} />
                </td>

                <td>
                    <div className="pl-learner">
                        <Skeleton width={36} height={36} circle />

                        <div className="pl-skeleton-stack">
                            <Skeleton width={115} height={12} />
                            <Skeleton width={90} height={9} />
                        </div>
                    </div>
                </td>

                <td>
                    <Skeleton width={110} height={12} />
                </td>
                <td>
                    <Skeleton width={100} height={12} />
                </td>
                <td>
                    <Skeleton width={85} height={12} />
                </td>
                <td>
                    <Skeleton width={95} height={12} />
                </td>

                <td>
                    <Skeleton
                        width={85}
                        height={24}
                        className="pl-skeleton-pill"
                    />
                </td>

                <td>
                    <Skeleton
                        width={85}
                        height={24}
                        className="pl-skeleton-pill"
                    />
                </td>
            </tr>
        ))}
    </>
);

/* =========================================
   STATUS BADGE
========================================= */

const StatusBadge = ({ value, type }) => {
    const normalized = String(value ?? "")
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, "_");

    const success = ["approved", "accepted", "completed", "success"];

    const pending = [
        "pending",
        "submitted",
        "under_review",
        "in_progress",
        "processing",
        "draft",
    ];

    let className = "pl-status--inactive";

    if (success.includes(normalized)) {
        className = "pl-status--active";
    } else if (pending.includes(normalized)) {
        className = "pl-status--pending";
    }

    const label = normalized
        ? normalized
              .split("_")
              .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
              .join(" ")
        : type === "review"
          ? "Not Reviewed"
          : "Unknown";

    return (
        <span className={`pl-status ${className}`}>
            <span />
            {label}
        </span>
    );
};

// Displays the export request notification using the shared Learner module design.
function ExportToast({ toast, onClose }) {
    useEffect(() => {
        if (!toast) return;

        const timer = setTimeout(onClose, 6500);

        return () => clearTimeout(timer);
    }, [toast, onClose]);

    if (!toast) return null;

    return (
        <div className="pl-toast-container">
            <div
                key={toast.key}
                className={`pl-export-toast pl-export-toast--${toast.type}`}
                role="status"
                aria-live="polite"
            >
                <div className="pl-toast-icon">
                    <CheckCircle2 size={22} strokeWidth={2.5} />
                </div>

                <div className="pl-toast-content">
                    <div className="pl-toast-heading">
                        <strong>{toast.title}</strong>
                        <span className="pl-toast-id">#{toast.exportId}</span>
                    </div>

                    <p>{toast.message}</p>

                    <span className="pl-toast-status">
                        <span className="pl-toast-pulse" />
                        Processing in background
                    </span>
                </div>

                <button
                    type="button"
                    className="pl-toast-close"
                    onClick={onClose}
                    aria-label="Close notification"
                >
                    ×
                </button>

                <div className="pl-toast-progress" />
            </div>
        </div>
    );
}

/* =========================================
    MAIN COMPONENT
    ========================================= */

/**
 * Displays the shared transaction list, optionally scoped to one Field Agent.
 */
export default function EventTransactionList({
    agentId = null,
    showHeader = true,
    title = "All Event Transactions",
}) {
    const [transactions, setTransactions] = useState([]);

    const [selectedTransactionId, setSelectedTransactionId] = useState(null);

    const openTransaction = (id) => {
        setSelectedTransactionId(id);
    };

    const closeTransaction = () => {
        setSelectedTransactionId(null);
    };

    const [summary, setSummary] = useState({
        total: 0,
        approved: 0,
        pending: 0,
        total_value: 0,
    });

    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);

    const [showAdvanced, setShowAdvanced] = useState(false);

    const [advancedDraft, setAdvancedDraft] = useState({
        ...EMPTY_FILTERS,
    });

    const [advancedFilters, setAdvancedFilters] = useState({
        ...EMPTY_FILTERS,
    });

    const [loading, setLoading] = useState(true);
    const [initialLoading, setInitialLoading] = useState(true);
    const [exporting, setExporting] = useState(false);

    const [error, setError] = useState("");
    const [refreshKey, setRefreshKey] = useState(0);

    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: PAGE_SIZE,
        total: 0,
    });

    const [toast, setToast] = useState(null);
    const [exportToast, setExportToast] = useState(null);

    // Displays the export confirmation with the ID returned by the API.
    const showExportToast = (exportId) => {
        setExportToast({
            key: `${exportId}-${Date.now()}`,
            type: "success",
            title: "Export Request Submitted",
            exportId,
            message:
                "Your event transactions export is being prepared. You can download it once processing is complete.",
        });
    };

    // Closes the export confirmation toast without restarting its timer on rerenders.
    const closeExportToast = useCallback(() => {
        setExportToast(null);
    }, []);

    /**
     * Displays a success or error toast using the Event Transactions toast UI.
     */
    const showToast = (message, type = "success") => {
        setToast({ message, type });
    };

    /**
     * Requests an Excel export using the current search and applied filters.
     */
    const exportTransactions = async () => {
        if (exporting || loading || pagination.total === 0) return;

        setExporting(true);
        setError("");

        try {
            const filters = {
                search: search.trim(),
                ...Object.fromEntries(
                    Object.entries(advancedFilters).filter(
                        ([, value]) => String(value ?? "").trim() !== "",
                    ),
                ),
            };

            // Include the selected agent so exports match the displayed listing.
            if (agentId !== null && agentId !== undefined) {
                filters.agent_id = agentId;
            }

            const response = await fetch(`${API_BASE}/export`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") ?? "",
                },
                body: JSON.stringify({ filters }),
            });

            const result = await response.json();

            if (!response.ok || result.success !== true) {
                throw new Error(
                    result.message || "Unable to start the Excel export.",
                );
            }

            showExportToast(result.export_id);
        } catch (error) {
            setError(error.message || "Unable to start the Excel export.");
            showToast(
                error.message || "Unable to start the Excel export.",
                "error",
            );
        } finally {
            setExporting(false);
        }
    };

    /* =====================================
       FILTER HANDLERS
    ===================================== */

    const updateAdvanced = (key, value) => {
        setAdvancedDraft((previous) => ({
            ...previous,
            [key]: value,
        }));
    };

    const applyAdvancedFilters = () => {
        if (
            advancedDraft.date_from &&
            advancedDraft.date_to &&
            advancedDraft.date_from > advancedDraft.date_to
        ) {
            setError("From Date cannot be later than To Date.");
            return;
        }

        setError("");
        setPage(1);
        setAdvancedFilters({ ...advancedDraft });
    };

    const clearAdvancedFilters = () => {
        setAdvancedDraft({ ...EMPTY_FILTERS });
        setAdvancedFilters({ ...EMPTY_FILTERS });
        setPage(1);
    };

    const resetFilters = () => {
        setSearch("");
        clearAdvancedFilters();
    };

    const hasActiveFilters =
        search.trim() !== "" ||
        Object.values(advancedFilters).some(
            (value) => String(value ?? "").trim() !== "",
        );

    /* =====================================
       API FETCHING
    ===================================== */

    useEffect(() => {
        const controller = new AbortController();

        async function loadTransactions() {
            setLoading(true);
            setError("");

            try {
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(PAGE_SIZE),
                    search: search.trim(),
                });

                // Restrict the listing to the selected Field Agent when supplied.
                if (agentId !== null && agentId !== undefined) {
                    params.set("agent_id", String(agentId));
                }

                Object.entries(advancedFilters).forEach(([key, value]) => {
                    const normalized = String(value ?? "").trim();

                    if (normalized !== "") {
                        params.set(key, normalized);
                    }
                });

                const response = await fetch(
                    `${API_BASE}?${params.toString()}`,
                    {
                        method: "GET",
                        credentials: "same-origin",
                        headers: {
                            Accept: "application/json",
                        },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error(
                        `Unable to load transactions (HTTP ${response.status}).`,
                    );
                }

                const result = await response.json();

                if (result.success !== true) {
                    throw new Error(
                        result.message || "Unable to load transaction records.",
                    );
                }

                if (controller.signal.aborted) return;

                setTransactions(Array.isArray(result.data) ? result.data : []);

                setSummary({
                    total: Number(result.summary?.total ?? 0),
                    approved: Number(result.summary?.approved ?? 0),
                    pending: Number(result.summary?.pending ?? 0),
                    total_value: Number(result.summary?.total_value ?? 0),
                });

                setPagination({
                    current_page: Number(
                        result.pagination?.current_page ?? page,
                    ),
                    last_page: Math.max(
                        1,
                        Number(result.pagination?.last_page ?? 1),
                    ),
                    per_page: Number(result.pagination?.per_page ?? PAGE_SIZE),
                    total: Number(result.pagination?.total ?? 0),
                });
            } catch (err) {
                if (err.name !== "AbortError") {
                    setError(err.message || "Something went wrong.");
                    setTransactions([]);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                    setInitialLoading(false);
                }
            }
        }

        loadTransactions();

        return () => controller.abort();
    }, [page, search, advancedFilters, refreshKey, agentId]);

    /**
     * Resets pagination when the selected Field Agent changes.
     */
    useEffect(() => {
        setPage(1);
    }, [agentId]);

    /* =====================================
       PAGINATION
    ===================================== */

    const currentPage = pagination.current_page;
    const totalPages = pagination.last_page;

    const startRecord = pagination.total
        ? (currentPage - 1) * pagination.per_page + 1
        : 0;

    const endRecord = Math.min(
        (currentPage - 1) * pagination.per_page + transactions.length,
        pagination.total,
    );

    const visibleTransactions = useMemo(() => transactions, [transactions]);

    /* =====================================
       SUMMARY CONFIGURATION
    ===================================== */

    const summaryCards = [
        {
            label: "Total Transactions",
            value: formatNumber(summary.total),
            caption: "All event records",
            icon: ReceiptText,
            color: "blue",
        },
        {
            label: "Approved",
            value: formatNumber(summary.approved),
            caption: "Approved transactions",
            icon: CheckCircle2,
            color: "green",
        },
        {
            label: "Pending Review",
            value: formatNumber(summary.pending),
            caption: "Awaiting review",
            icon: Clock3,
            color: "purple",
        },
        {
            label: "Total Event Value",
            value: formatAmount(summary.total_value),
            caption: "Combined event value",
            icon: IndianRupee,
            color: "blue",
        },
    ];

    /* =====================================
       RENDER
    ===================================== */

    return (
        <main className="partner-learners event-transactions pl-page-loading-wrapper">
            <ExportToast toast={exportToast} onClose={closeExportToast} />

            {toast && (
                <div
                    role="status"
                    className={`et-toast et-toast--${toast.type}`}
                >
                    <span>{toast.message}</span>

                    <button
                        type="button"
                        onClick={() => setToast(null)}
                        aria-label="Close notification"
                    >
                        ×
                    </button>
                </div>
            )}
            {/* PAGE HEADER */}
            {showHeader && (
                <header className="pl-page-header">
                    <div>
                        <div className="pl-breadcrumb">
                            Placement Partner Portal <span>/</span> Event
                            Transactions
                        </div>

                        <h1>Event Transactions</h1>

                        <p>
                            Manage and track beneficiary event transactions in
                            one place.
                        </p>
                    </div>

                    <button
                        type="button"
                        className="pl-button pl-button--primary"
                        onClick={exportTransactions}
                        disabled={
                            loading || exporting || pagination.total === 0
                        }
                    >
                        {exporting ? (
                            <RefreshCw size={17} className="pl-spin" />
                        ) : (
                            <Download size={17} />
                        )}

                        {exporting ? "Preparing Request..." : "Export Excel"}
                    </button>
                </header>
            )}
            {/* SUMMARY CARDS */}

            <section className="pl-summary" aria-busy={initialLoading}>
                {initialLoading ? (
                    <>
                        <SummarySkeleton color="blue" />
                        <SummarySkeleton color="green" />
                        <SummarySkeleton color="purple" />
                        <SummarySkeleton color="blue" />
                    </>
                ) : (
                    summaryCards.map((item) => {
                        const Icon = item.icon;

                        return (
                            <article
                                key={item.label}
                                className={`pl-summary-card pl-summary-card--${item.color}`}
                            >
                                <span className="pl-summary-icon">
                                    <Icon size={20} />
                                </span>

                                <div className="pl-summary-content">
                                    <span className="pl-summary-label">
                                        {item.label}
                                    </span>

                                    <strong>{item.value}</strong>

                                    <span className="pl-summary-caption">
                                        {item.caption}
                                    </span>
                                </div>

                                <span className="pl-summary-arrow">
                                    <ChevronRight size={17} />
                                </span>
                            </article>
                        );
                    })
                )}
            </section>

            {/* TABLE CARD */}

            <section className="pl-table-card">
                <div className="pl-table-heading">
                    <div>
                        <h2>{title}</h2>
                        <p>
                            Search, filter and view event transaction records.
                        </p>
                    </div>

                    <button
                        type="button"
                        className="pl-button pl-button--secondary"
                        disabled={loading}
                        onClick={() =>
                            setRefreshKey((previous) => previous + 1)
                        }
                    >
                        <RefreshCw
                            size={15}
                            className={loading ? "pl-spin" : ""}
                        />
                        Refresh
                    </button>
                </div>

                {/* SEARCH TOOLBAR */}

                <div className="pl-filters">
                    <label className="pl-search">
                        <Search size={18} />

                        <input
                            type="search"
                            placeholder="Search ID, beneficiary, mobile or event..."
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                                setPage(1);
                            }}
                        />

                        {search && (
                            <button
                                type="button"
                                aria-label="Clear search"
                                onClick={() => {
                                    setSearch("");
                                    setPage(1);
                                }}
                            >
                                <X size={16} />
                            </button>
                        )}
                    </label>

                    <div className="pl-advanced-toolbar">
                        <button
                            type="button"
                            className="pl-button pl-button--secondary"
                            onClick={() =>
                                setShowAdvanced((previous) => !previous)
                            }
                        >
                            <SlidersHorizontal size={16} />
                            Advanced Search
                            {showAdvanced ? " −" : " +"}
                        </button>
                    </div>
                </div>

                {/* ADVANCED SEARCH PANEL */}

                {showAdvanced && (
                    <div className="pl-advanced-panel">
                        <div className="pl-advanced-heading">
                            <div>
                                <h3>Advanced Transaction Search</h3>

                                <p>
                                    Combine multiple filters to find matching
                                    event records.
                                </p>
                            </div>
                        </div>

                        <div className="pl-advanced-grid">
                            {FILTER_FIELDS.map((field) => (
                                <label
                                    className="pl-advanced-field"
                                    key={field.key}
                                >
                                    <span>{field.label}</span>

                                    <input
                                        type="text"
                                        value={advancedDraft[field.key]}
                                        placeholder={field.placeholder}
                                        onChange={(event) =>
                                            updateAdvanced(
                                                field.key,
                                                event.target.value,
                                            )
                                        }
                                    />
                                </label>
                            ))}

                            <label className="pl-advanced-field">
                                <span>Review Status</span>

                                <select
                                    value={advancedDraft.review_status}
                                    onChange={(event) =>
                                        updateAdvanced(
                                            "review_status",
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        All Review Statuses
                                    </option>
                                    <option value="pending">Pending</option>
                                    <option value="under_review">
                                        Under Review
                                    </option>
                                    <option value="approved">Approved</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </label>

                            <label className="pl-advanced-field">
                                <span>Workflow Status</span>

                                <select
                                    value={advancedDraft.workflow_status}
                                    onChange={(event) =>
                                        updateAdvanced(
                                            "workflow_status",
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        All Workflow Statuses
                                    </option>
                                    <option value="draft">Draft</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">
                                        In Progress
                                    </option>
                                    <option value="completed">Completed</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </label>

                            <label className="pl-advanced-field">
                                <span>Submitted From</span>

                                <input
                                    type="date"
                                    value={advancedDraft.date_from}
                                    onChange={(event) =>
                                        updateAdvanced(
                                            "date_from",
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>

                            <label className="pl-advanced-field">
                                <span>Submitted To</span>

                                <input
                                    type="date"
                                    min={advancedDraft.date_from || undefined}
                                    value={advancedDraft.date_to}
                                    onChange={(event) =>
                                        updateAdvanced(
                                            "date_to",
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                        </div>

                        {/* RESULT COUNT LEFT / BUTTONS RIGHT */}

                        <div className="pl-advanced-actions">
                            <div className="pl-result-count">
                                <div className="pl-result-count__icon">
                                    <ReceiptText size={20} />
                                </div>

                                <div className="pl-result-count__info">
                                    <span className="pl-result-count__label">
                                        Search Results
                                    </span>

                                    <strong>
                                        {loading ? (
                                            <Skeleton width={65} height={23} />
                                        ) : (
                                            formatNumber(pagination.total)
                                        )}
                                    </strong>

                                    <span className="pl-result-count__caption">
                                        {loading
                                            ? "Searching transactions..."
                                            : "Matching transaction records"}
                                    </span>
                                </div>

                                <span className="pl-result-count__arrow">
                                    <ChevronRight size={17} />
                                </span>
                            </div>

                            <div className="pl-advanced-action-buttons">
                                <button
                                    type="button"
                                    className="pl-button pl-button--secondary pl-clear-btn"
                                    onClick={clearAdvancedFilters}
                                >
                                    <X size={15} />
                                    Clear All
                                </button>

                                <button
                                    type="button"
                                    className="pl-button pl-button--primary pl-apply-btn"
                                    onClick={applyAdvancedFilters}
                                    disabled={loading}
                                >
                                    <Search size={16} />
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* CLEAR ACTIVE FILTERS */}

                {hasActiveFilters && (
                    <button
                        type="button"
                        className="pl-button pl-button--secondary"
                        onClick={resetFilters}
                    >
                        <X size={15} />
                        Clear filters
                    </button>
                )}

                {/* ERROR MESSAGE */}

                {error && (
                    <div className="pl-error" role="alert">
                        <span>{error}</span>

                        <button
                            type="button"
                            onClick={() =>
                                setRefreshKey((previous) => previous + 1)
                            }
                        >
                            Try again
                        </button>
                    </div>
                )}

                {/* DATA TABLE */}

                <div className="pl-table-scroll" aria-busy={loading}>
                    <table className="pl-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Beneficiary</th>
                                <th>Event Type</th>
                                <th>Event Category</th>
                                <th>Event Value</th>
                                <th>Submitted On</th>
                                <th>Review Status</th>
                                <th>Workflow Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                <TransactionTableSkeleton />
                            ) : error ? (
                                <tr>
                                    <td colSpan={8} className="pl-empty">
                                        Transaction records could not be loaded.
                                    </td>
                                </tr>
                            ) : visibleTransactions.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="pl-empty">
                                        <ReceiptText size={26} />

                                        <strong>No transactions found</strong>

                                        <span>
                                            Try changing your search or filters.
                                        </span>
                                    </td>
                                </tr>
                            ) : (
                                visibleTransactions.map((transaction) => (
                                    <tr
                                        key={transaction.id}
                                        className="et-clickable-row"
                                        tabIndex={0}
                                        role="button"
                                        aria-label={`View event transaction ${transaction.id}`}
                                        onClick={() =>
                                            openTransaction(transaction.id)
                                        }
                                        onKeyDown={(event) => {
                                            if (
                                                event.key === "Enter" ||
                                                event.key === " "
                                            ) {
                                                event.preventDefault();
                                                openTransaction(transaction.id);
                                            }
                                        }}
                                    >
                                        <td>
                                            <strong>#{transaction.id}</strong>
                                        </td>

                                        <td>
                                            <div className="pl-learner">
                                                <span className="pl-avatar">
                                                    {initials(
                                                        transaction.beneficiary_name,
                                                    )}
                                                </span>

                                                <div className="et-beneficiary-info">
                                                    <strong>
                                                        {transaction.beneficiary_name ||
                                                            "—"}
                                                    </strong>

                                                    <small>
                                                        {transaction.beneficiary_phone_number ||
                                                            "—"}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div className="et-beneficiary-info">
                                                <strong>
                                                    {transaction.event_type_name ||
                                                        "—"}
                                                </strong>
                                                <span>
                                                    {" "}
                                                    {transaction.event_category_name ||
                                                        "—"}
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            {transaction.event_category_name ||
                                                "—"}
                                        </td>

                                        <td>
                                            <strong className="et-amount">
                                                {transaction.event_value == null
                                                    ? "—"
                                                    : formatAmount(
                                                          transaction.event_value,
                                                      )}
                                            </strong>
                                        </td>

                                        <td>
                                            <span className="pl-date">
                                                <CalendarDays size={14} />

                                                {formatDate(
                                                    transaction.event_date_submitted,
                                                )}
                                            </span>
                                        </td>

                                        <td>
                                            <StatusBadge
                                                value={
                                                    transaction.review_status
                                                }
                                                type="review"
                                            />
                                        </td>

                                        <td>
                                            <StatusBadge
                                                value={
                                                    transaction.workflow_status
                                                }
                                                type="workflow"
                                            />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* PAGINATION */}

                <footer className="pl-pagination">
                    <span>
                        {loading ? (
                            <Skeleton width={180} height={12} />
                        ) : (
                            <>
                                Showing {startRecord}–{endRecord} of{" "}
                                {formatNumber(pagination.total)} transactions
                            </>
                        )}
                    </span>

                    <div className="pl-pagination-controls">
                        <button
                            type="button"
                            aria-label="Previous page"
                            disabled={currentPage <= 1 || loading}
                            onClick={() =>
                                setPage((previous) => Math.max(1, previous - 1))
                            }
                        >
                            <ChevronLeft size={17} />
                        </button>

                        <span>
                            {currentPage} / {totalPages}
                        </span>

                        <button
                            type="button"
                            aria-label="Next page"
                            disabled={currentPage >= totalPages || loading}
                            onClick={() =>
                                setPage((previous) =>
                                    Math.min(totalPages, previous + 1),
                                )
                            }
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </footer>
            </section>

            {selectedTransactionId !== null && (
                <EventTransactionDrawer
                    transactionId={selectedTransactionId}
                    onClose={closeTransaction}
                />
            )}
        </main>
    );
}
