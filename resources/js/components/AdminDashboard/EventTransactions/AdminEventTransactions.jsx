import { useCallback, useEffect, useMemo, useState } from "react";
import {
    Search,
    SlidersHorizontal,
    RotateCcw,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Eye,
    Pencil,
    Trash2,
    CalendarDays,
    X,
    LoaderCircle,
    FileSearch,
    Users,
    Filter,
    CheckCircle2,
    Clock3,
    XCircle,
} from "lucide-react";

import "./EventTransactions.css";
import EventReviewPerformanceChart from "../EventReviewPerformanceChart.jsx";
import EventTransactionPieChart from "../EventTransactionPieChart.jsx";
import EventTransactionBarChart from "../EventTransactionBarChart.jsx";
import TransactionActivityChart from "./TransactionActivityChart";

const INITIAL_FILTERS = {
    search: "",
    beneficiary_name: "",
    mobile: "",
    event_type_id: "",
    event_category: "",
    partner_id: "",
    partner_center_id: "",
    placement_partner_id: "",
    field_agent_id: "",
    workflow_status: "",
    review_status: "",
    date_from: "",
    date_to: "",
};

/**
 * Convert a filter object into URL query parameters.
 */
function createQueryString(filters, page = 1) {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value !== "" && value !== null && value !== undefined) {
            params.set(key, value);
        }
    });

    params.set("page", String(page));
    params.set("per_page", "20");

    return params.toString();
}

/**
 * Format a transaction value as Indian currency.
 */
function formatCurrency(value) {
    if (value === null || value === undefined || value === "") {
        return "—";
    }

    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2,
    }).format(Number(value));
}

/**
 * Convert a status value into a readable label.
 */
function formatStatus(value) {
    if (!value) return "Unknown";

    return value
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

/**
 * Return the CSS modifier for a transaction status.
 */
function getStatusClass(value) {
    const status = String(value || "").toLowerCase();

    if (["approved", "accepted"].includes(status)) return "success";
    if (status === "rejected") return "danger";
    if (["returned", "return"].includes(status)) return "warning";
    if (["under_review", "pending", "open"].includes(status)) return "info";
    if (status === "draft") return "muted";
    if (["submitted", "resubmitted"].includes(status)) return "purple";

    return "muted";
}

export default function AdminEventTransactions({
    listingUrl,
    filterOptionsUrl,
    csrfToken,
}) {
    const [draftFilters, setDraftFilters] = useState(INITIAL_FILTERS);
    const [appliedFilters, setAppliedFilters] = useState(INITIAL_FILTERS);
    const [filterOptions, setFilterOptions] = useState({});
    const [transactions, setTransactions] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
        from: 0,
        to: 0,
    });

    const [summary, setSummary] = useState({
        total: 0,
        approved: 0,
        pending_review: 0,
        rejected: 0,
    });

    const [loading, setLoading] = useState(true);
    const [optionsLoading, setOptionsLoading] = useState(true);
    const [error, setError] = useState("");
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [page, setPage] = useState(1);
    const [deletingId, setDeletingId] = useState(null);

    /**
     * Load advanced search dropdown options from Laravel.
     */
    const loadFilterOptions = useCallback(async () => {
        try {
            setOptionsLoading(true);

            const response = await fetch(filterOptionsUrl, {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });

            if (!response.ok) {
                throw new Error("Unable to load filter options.");
            }

            const result = await response.json();
            setFilterOptions(result);
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setOptionsLoading(false);
        }
    }, [filterOptionsUrl]);

    /**
     * Load the transaction list using the applied filters and page.
     */
    const loadTransactions = useCallback(async () => {
        try {
            setLoading(true);
            setError("");

            const query = createQueryString(appliedFilters, page);
            const response = await fetch(`${listingUrl}?${query}`, {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });

            if (!response.ok) {
                throw new Error("Unable to load event transactions.");
            }

            const result = await response.json();

            setTransactions(result.data || []);
            setMeta(result.meta || {});

            // Update aggregate summary counts returned by Laravel.
            setSummary({
                total: Number(result.summary?.total ?? result.meta?.total ?? 0),
                approved: Number(result.summary?.approved ?? 0),
                pending_review: Number(result.summary?.pending_review ?? 0),
                rejected: Number(result.summary?.rejected ?? 0),
            });
        } catch (requestError) {
            setError(requestError.message);
            setTransactions([]);
        } finally {
            setLoading(false);
        }
    }, [listingUrl, appliedFilters, page]);

    useEffect(() => {
        loadFilterOptions();
    }, [loadFilterOptions]);

    useEffect(() => {
        loadTransactions();
    }, [loadTransactions]);

    /**
     * Update one field in the draft advanced search form.
     */
    function updateFilter(event) {
        const { name, value } = event.target;

        setDraftFilters((current) => ({
            ...current,
            [name]: value,
        }));
    }

    /**
     * Apply the draft filters and restart pagination from page one.
     */
    function applyFilters(event) {
        event.preventDefault();

        setAppliedFilters({ ...draftFilters });
        setPage(1);
    }

    /**
     * Clear all search filters and restore the default listing.
     */
    function resetFilters() {
        setDraftFilters({ ...INITIAL_FILTERS });
        setAppliedFilters({ ...INITIAL_FILTERS });
        setPage(1);
    }

    /**
     * Remove one applied filter without clearing the remaining filters.
     */
    function removeAppliedFilter(key) {
        const updatedFilters = {
            ...appliedFilters,
            [key]: "",
        };

        setDraftFilters(updatedFilters);
        setAppliedFilters(updatedFilters);
        setPage(1);
    }

    /**
     * Delete a transaction using the existing Laravel resource route.
     */
    async function deleteTransaction(transaction) {
        const confirmed = window.confirm(
            `Delete event transaction #${transaction.id}?`,
        );

        if (!confirmed) return;

        try {
            setDeletingId(transaction.id);

            const response = await fetch(transaction.urls.delete, {
                method: "DELETE",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (!response.ok) {
                throw new Error("Unable to delete this transaction.");
            }

            await loadTransactions();
        } catch (requestError) {
            window.alert(requestError.message);
        } finally {
            setDeletingId(null);
        }
    }

    const activeFilters = useMemo(() => {
        const labels = {
            search: "Search",
            beneficiary_name: "Beneficiary",
            mobile: "Mobile",
            event_type_id: "Event Type",
            event_category: "Category",
            partner_id: "Partner",
            partner_center_id: "Center",
            placement_partner_id: "Placement Partner",
            field_agent_id: "Field Agent",
            workflow_status: "Workflow",
            review_status: "Review",
            date_from: "From",
            date_to: "To",
        };

        return Object.entries(appliedFilters)
            .filter(([, value]) => value !== "")
            .map(([key, value]) => ({
                key,
                label: labels[key] || key,
                value,
            }));
    }, [appliedFilters]);

    /**
     * Render a reusable labelled select field for advanced filters.
     */
    function renderSelect(name, label, options, placeholder) {
        return (
            <label className="aet-field" key={name}>
                <span>{label}</span>
                <select
                    name={name}
                    value={draftFilters[name]}
                    onChange={updateFilter}
                    disabled={optionsLoading}
                >
                    <option value="">{placeholder}</option>

                    {(options || []).map((option) => {
                        const value =
                            typeof option === "string"
                                ? option
                                : (option.id ?? option.value);

                        const text =
                            typeof option === "string"
                                ? option
                                : (option.name ?? option.label);

                        return (
                            <option value={value} key={value}>
                                {text}
                            </option>
                        );
                    })}
                </select>
            </label>
        );
    }

    return (
        <section className="aet-page">
            <div className="aet-summary-grid">
                <div className="aet-summary-card aet-summary-card--total">
                    <div className="aet-summary-icon">
                        <FileSearch size={21} />
                    </div>
                    <div className="aet-summary-content">
                        <span className="aet-summary-label">
                            Total Transactions
                        </span>
                        <strong className="aet-summary-value">
                            {summary.total.toLocaleString("en-IN")}
                        </strong>
                        <span className="aet-summary-description">
                            All matching records
                        </span>
                    </div>
                </div>

                <div className="aet-summary-card aet-summary-card--approved">
                    <div className="aet-summary-icon">
                        <CheckCircle2 size={21} />
                    </div>
                    <div className="aet-summary-content">
                        <span className="aet-summary-label">Approved</span>
                        <strong className="aet-summary-value">
                            {summary.approved.toLocaleString("en-IN")}
                        </strong>
                        <span className="aet-summary-description">
                            Approved transactions
                        </span>
                    </div>
                </div>

                <div className="aet-summary-card aet-summary-card--pending">
                    <div className="aet-summary-icon">
                        <Clock3 size={21} />
                    </div>
                    <div className="aet-summary-content">
                        <span className="aet-summary-label">
                            Pending Review
                        </span>
                        <strong className="aet-summary-value">
                            {summary.pending_review.toLocaleString("en-IN")}
                        </strong>
                        <span className="aet-summary-description">
                            Awaiting review decision
                        </span>
                    </div>
                </div>

                <div className="aet-summary-card aet-summary-card--rejected">
                    <div className="aet-summary-icon">
                        <XCircle size={21} />
                    </div>
                    <div className="aet-summary-content">
                        <span className="aet-summary-label">Rejected</span>
                        <strong className="aet-summary-value">
                            {summary.rejected.toLocaleString("en-IN")}
                        </strong>
                        <span className="aet-summary-description">
                            Rejected submissions
                        </span>
                    </div>
                </div>
            </div>
            {/* Summary Cards - Existing 4 Column Grid */}
            <section className="aet-page">
                <div className="aet-summary-grid">
                    {/* Existing four summary cards */}
                </div>
            </section>

            {/* Analytics Charts - Separate 2 Column Grid */}
            <section className="aet-page">
                <div className="aet-charts-grid">
                    <EventReviewPerformanceChart type="review-performance" />

                    <EventTransactionPieChart type="partner-transactions" />
                </div>
            </section>

            <section className="aet-page">
                <div className="aet-charts-grid">
                    <TransactionActivityChart />

                    <EventTransactionBarChart type="review-performance" />
                </div>
            </section>

            <div className="aet-directory">
                <div className="aet-directory-header">
                    <div>
                        <span className="aet-eyebrow">
                            TRANSACTION DIRECTORY
                        </span>
                        <h2>All Event Transactions</h2>
                        <p>
                            Search, filter and manage event submissions from one
                            place.
                        </p>
                    </div>

                    <div className="aet-total">
                        <span>
                            {Number(meta.total || 0).toLocaleString("en-IN")}
                        </span>
                        <small>Matching records</small>
                    </div>
                </div>

                <div className="aet-search-row">
                    <div className="aet-search-box">
                        <Search size={18} />
                        <input
                            type="search"
                            name="search"
                            value={draftFilters.search}
                            onChange={updateFilter}
                            onKeyDown={(event) => {
                                if (event.key === "Enter") {
                                    applyFilters(event);
                                }
                            }}
                            placeholder="Search beneficiary, mobile, event..."
                        />
                        {draftFilters.search && (
                            <button
                                type="button"
                                aria-label="Clear search"
                                onClick={() =>
                                    setDraftFilters((current) => ({
                                        ...current,
                                        search: "",
                                    }))
                                }
                            >
                                <X size={15} />
                            </button>
                        )}
                    </div>

                    <button
                        type="button"
                        className={`aet-filter-toggle ${filtersOpen ? "is-open" : ""}`}
                        onClick={() => setFiltersOpen((current) => !current)}
                    >
                        <SlidersHorizontal size={17} />
                        Advanced Search
                        <ChevronDown size={16} />
                    </button>
                </div>

                {filtersOpen && (
                    <form className="aet-filter-panel" onSubmit={applyFilters}>
                        <div className="aet-filter-panel-heading">
                            <div>
                                <Filter size={16} />
                                <strong>Refine transactions</strong>
                            </div>
                            <span>All filters are optional</span>
                        </div>

                        <div className="aet-filter-grid">
                            <label className="aet-field">
                                <span>Beneficiary Name</span>
                                <input
                                    name="beneficiary_name"
                                    value={draftFilters.beneficiary_name}
                                    onChange={updateFilter}
                                    placeholder="Enter beneficiary name"
                                />
                            </label>

                            <label className="aet-field">
                                <span>Mobile Number</span>
                                <input
                                    name="mobile"
                                    value={draftFilters.mobile}
                                    onChange={updateFilter}
                                    placeholder="Enter mobile number"
                                />
                            </label>

                            {renderSelect(
                                "event_type_id",
                                "Event Type",
                                filterOptions.event_types,
                                "All event types",
                            )}

                            {renderSelect(
                                "event_category",
                                "Event Category",
                                filterOptions.event_categories,
                                "All categories",
                            )}

                            {renderSelect(
                                "partner_id",
                                "Partner",
                                filterOptions.partners,
                                "All partners",
                            )}

                            {renderSelect(
                                "partner_center_id",
                                "Partner Center",
                                filterOptions.partner_centers,
                                "All centers",
                            )}

                            {renderSelect(
                                "placement_partner_id",
                                "Placement Partner",
                                filterOptions.placement_partners,
                                "All placement partners",
                            )}

                            {renderSelect(
                                "field_agent_id",
                                "Field Agent",
                                filterOptions.field_agents,
                                "All field agents",
                            )}

                            {renderSelect(
                                "workflow_status",
                                "Workflow Status",
                                filterOptions.workflow_statuses,
                                "All workflow statuses",
                            )}

                            {renderSelect(
                                "review_status",
                                "Review Status",
                                filterOptions.review_statuses,
                                "All review statuses",
                            )}

                            <label className="aet-field">
                                <span>Event Date From</span>
                                <div className="aet-date-input">
                                    <CalendarDays size={16} />
                                    <input
                                        type="date"
                                        name="date_from"
                                        value={draftFilters.date_from}
                                        onChange={updateFilter}
                                    />
                                </div>
                            </label>

                            <label className="aet-field">
                                <span>Event Date To</span>
                                <div className="aet-date-input">
                                    <CalendarDays size={16} />
                                    <input
                                        type="date"
                                        name="date_to"
                                        value={draftFilters.date_to}
                                        onChange={updateFilter}
                                        min={
                                            draftFilters.date_from || undefined
                                        }
                                    />
                                </div>
                            </label>
                        </div>

                        <div className="aet-filter-actions">
                            <button
                                type="button"
                                className="aet-reset-button"
                                onClick={resetFilters}
                            >
                                <RotateCcw size={15} />
                                Reset filters
                            </button>

                            <button type="submit" className="aet-apply-button">
                                <Search size={16} />
                                Apply Filters
                            </button>
                        </div>
                    </form>
                )}

                {activeFilters.length > 0 && (
                    <div className="aet-active-filters">
                        <span>Active filters</span>

                        {activeFilters.map((filter) => (
                            <button
                                type="button"
                                key={filter.key}
                                className="aet-filter-chip"
                                onClick={() => removeAppliedFilter(filter.key)}
                            >
                                <b>{filter.label}:</b> {filter.value}
                                <X size={13} />
                            </button>
                        ))}

                        <button
                            type="button"
                            className="aet-clear-all"
                            onClick={resetFilters}
                        >
                            Clear all
                        </button>
                    </div>
                )}

                {error && (
                    <div className="aet-error">
                        <FileSearch size={17} />
                        {error}
                    </div>
                )}

                <div className="aet-table-topline">
                    <div>
                        <Users size={17} />
                        <strong>Transaction Records</strong>
                        <span>
                            {Number(meta.total || 0).toLocaleString("en-IN")}{" "}
                            records
                        </span>
                    </div>

                    <span className="aet-page-size">20 records per page</span>
                </div>

                <div className="aet-table-wrap">
                    <table className="aet-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Beneficiary</th>
                                <th>Event</th>
                                <th>Partner / Center</th>
                                <th>Field Agent</th>
                                <th>Event Date</th>
                                <th>Value</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan="9" className="aet-loading">
                                        <LoaderCircle
                                            className="aet-spin"
                                            size={24}
                                        />
                                        <span>Loading transactions...</span>
                                    </td>
                                </tr>
                            ) : transactions.length === 0 ? (
                                <tr>
                                    <td colSpan="9" className="aet-empty">
                                        <FileSearch size={32} />
                                        <strong>No transactions found</strong>
                                        <span>
                                            Try changing your search or advanced
                                            filters.
                                        </span>
                                    </td>
                                </tr>
                            ) : (
                                transactions.map((transaction) => (
                                    <tr key={transaction.id}>
                                        <td>
                                            <strong className="aet-id">
                                                #{transaction.id}
                                            </strong>
                                        </td>

                                        <td>
                                            <div className="aet-person">
                                                <span className="aet-avatar">
                                                    {(
                                                        transaction.beneficiary_name ||
                                                        "B"
                                                    )
                                                        .trim()
                                                        .charAt(0)
                                                        .toUpperCase()}
                                                </span>
                                                <div>
                                                    <strong>
                                                        {transaction.beneficiary_name ||
                                                            "—"}
                                                    </strong>
                                                    <small>
                                                        {transaction.beneficiary_phone_number ||
                                                            "Mobile not available"}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div className="aet-cell-stack">
                                                <strong>
                                                    {transaction.event_type_name ||
                                                        "—"}
                                                </strong>
                                                <small>
                                                    {transaction.event_category_name ||
                                                        "—"}
                                                </small>
                                            </div>
                                        </td>

                                        <td>
                                            <div className="aet-cell-stack">
                                                <strong>
                                                    {transaction.partner_name ||
                                                        "—"}
                                                </strong>
                                                <small>
                                                    {transaction.partner_center_name ||
                                                        "No center"}
                                                </small>
                                            </div>
                                        </td>

                                        <td>
                                            <div className="aet-cell-stack">
                                                <strong>
                                                    {transaction.field_agent_name ||
                                                        "—"}
                                                </strong>
                                                <small>
                                                    {transaction.field_agent_sakhi_id ||
                                                        "—"}
                                                </small>
                                            </div>
                                        </td>

                                        <td className="aet-date-cell">
                                            {transaction.event_create_date ||
                                                "—"}
                                        </td>

                                        <td className="aet-value-cell">
                                            {formatCurrency(
                                                transaction.event_value,
                                            )}
                                        </td>

                                        <td>
                                            <div className="aet-status-stack">
                                                <span
                                                    className={`aet-status aet-status--${getStatusClass(
                                                        transaction.review_status,
                                                    )}`}
                                                >
                                                    Review:{" "}
                                                    {formatStatus(
                                                        transaction.review_status,
                                                    )}
                                                </span>

                                                <span
                                                    className={`aet-status aet-status--${getStatusClass(
                                                        transaction.workflow_status,
                                                    )}`}
                                                >
                                                    {formatStatus(
                                                        transaction.workflow_status,
                                                    )}
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <div className="aet-actions">
                                                <a
                                                    href={transaction.urls.show}
                                                    title="View transaction"
                                                >
                                                    <Eye size={16} />
                                                </a>

                                                {transaction.permissions
                                                    .update && (
                                                    <a
                                                        href={
                                                            transaction.urls
                                                                .edit
                                                        }
                                                        title="Edit transaction"
                                                    >
                                                        <Pencil size={16} />
                                                    </a>
                                                )}

                                                {transaction.permissions
                                                    .delete && (
                                                    <button
                                                        type="button"
                                                        title="Delete transaction"
                                                        disabled={
                                                            deletingId ===
                                                            transaction.id
                                                        }
                                                        onClick={() =>
                                                            deleteTransaction(
                                                                transaction,
                                                            )
                                                        }
                                                    >
                                                        {deletingId ===
                                                        transaction.id ? (
                                                            <LoaderCircle
                                                                size={16}
                                                                className="aet-spin"
                                                            />
                                                        ) : (
                                                            <Trash2 size={16} />
                                                        )}
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <footer className="aet-pagination">
                    <span>
                        Showing <strong>{meta.from || 0}</strong>–
                        <strong>{meta.to || 0}</strong> of{" "}
                        <strong>
                            {Number(meta.total || 0).toLocaleString("en-IN")}
                        </strong>
                    </span>

                    <div className="aet-pagination-controls">
                        <button
                            type="button"
                            disabled={loading || page <= 1}
                            onClick={() =>
                                setPage((current) => Math.max(1, current - 1))
                            }
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>

                        <span>
                            Page <strong>{meta.current_page || 1}</strong> of{" "}
                            <strong>{meta.last_page || 1}</strong>
                        </span>

                        <button
                            type="button"
                            disabled={loading || page >= (meta.last_page || 1)}
                            onClick={() =>
                                setPage((current) =>
                                    Math.min(meta.last_page || 1, current + 1),
                                )
                            }
                            aria-label="Next page"
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </footer>
            </div>
        </section>
    );
}
