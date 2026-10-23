import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
    Users,
    BriefcaseBusiness,
    CircleCheck,
    AlertCircle,
    Search,
    Download,
    SlidersHorizontal,
    ChevronLeft,
    ChevronRight,
    Eye,
    MapPin,
    X,
    Mail,
    Phone,
    RefreshCw,
} from "lucide-react";
import "./FieldAgents.css";

const API_BASE = "/api/partner/field-agents";
const PAGE_SIZE = 10;
const EMPTY_SUMMARY = {
    total_agents: 0,
    total_jobs: 0,
    total_accepted: 0,
    action_required: 0,
};
const EMPTY_FILTERS = { partners: [], states: [], districts: [], centers: [] };
const STATUS = [
    { label: "Total", className: "total" },
    { label: "Submitted", className: "submitted" },
    { label: "Action Required", className: "required" },
    { label: "Accepted", className: "accepted" },
    { label: "Rejected", className: "rejected" },
];

async function getJSON(url, signal) {
    const response = await fetch(url, {
        method: "GET",
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        signal,
    });
    if (!response.ok) {
        if (response.status === 401 || response.status === 403)
            throw new Error(
                "Your session has expired or access is denied. Please sign in again.",
            );
        throw new Error(
            `Unable to load data (HTTP ${response.status}). Please retry.`,
        );
    }
    const json = await response.json();
    if (!json.success || !json.data)
        throw new Error(json.message || "Invalid API response.");
    return json.data;
}

function StatusCounters({ values = [] }) {
    return (
        <div className="fa-status-group">
            {STATUS.map((status, i) => (
                <span
                    key={status.label}
                    className={`fa-status-pill ${status.className}`}
                    title={`${status.label}: ${values[i] ?? 0}`}
                >
                    {values[i] ?? 0}
                </span>
            ))}
        </div>
    );
}
function SummaryCard({
    title,
    value,
    icon: Icon,
    variant,
    description,
    loading = false,
    onClick,
    prefix = "",
}) {
    return (
        <div className={`fa-summary-card ${variant}`}>
            <div className="fa-summary-icon">
                <Icon size={20} strokeWidth={2} />
            </div>

            <div className="fa-summary-content">
                {loading ? (
                    <>
                        <span className="fa-summary-skeleton fa-sk-title" />
                        <span className="fa-summary-skeleton fa-sk-value" />
                        <span className="fa-summary-skeleton fa-sk-desc" />
                    </>
                ) : (
                    <>
                        <span className="fa-summary-title">{title}</span>

                        <strong className="fa-summary-value">
                            {prefix}
                            {Number(value ?? 0).toLocaleString("en-IN")}
                        </strong>

                        <small className="fa-summary-description">
                            {description}
                        </small>
                    </>
                )}
            </div>

            <button
                type="button"
                className="fa-summary-arrow"
                onClick={onClick}
                disabled={loading || !onClick}
                aria-label={`View ${title}`}
            >
                <ChevronRight size={17} strokeWidth={2} />
            </button>
        </div>
    );
}

function TableSkeleton({ rows }) {
    return Array.from({ length: rows }, (_, i) => (
        <tr
            key={`skeleton-${i}`}
            className="fa-skeleton-row"
            aria-hidden="true"
        >
            <td>
                <span className="fa-skeleton fa-sk-sn" />
            </td>
            <td>
                <div className="fa-agent-cell">
                    <span className="fa-skeleton fa-sk-avatar" />
                    <div className="fa-sk-lines">
                        <span className="fa-skeleton fa-sk-name" />
                        <span className="fa-skeleton fa-sk-id" />
                    </div>
                </div>
            </td>
            <td>
                <div className="fa-sk-lines">
                    <span className="fa-skeleton fa-sk-contact" />
                    <span className="fa-skeleton fa-sk-contact-short" />
                </div>
            </td>
            <td>
                <div className="fa-sk-lines">
                    <span className="fa-skeleton fa-sk-name" />
                    <span className="fa-skeleton fa-sk-id" />
                </div>
            </td>
            <td>
                <div className="fa-sk-pills">
                    {STATUS.map((s) => (
                        <span
                            key={s.label}
                            className="fa-skeleton fa-sk-pill"
                        />
                    ))}
                </div>
            </td>
            <td>
                <div className="fa-sk-pills">
                    {STATUS.map((s) => (
                        <span
                            key={s.label}
                            className="fa-skeleton fa-sk-pill"
                        />
                    ))}
                </div>
            </td>
            <td>
                <span className="fa-skeleton fa-sk-button" />
            </td>
        </tr>
    ));
}

export default function FieldAgents({ onViewDetails }) {
    const [search, setSearch] = useState("");
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [state, setState] = useState("");
    const [district, setDistrict] = useState("");
    const [center, setCenter] = useState("");
    const [status, setStatus] = useState("");
    const [showFilters, setShowFilters] = useState(false);
    const [page, setPage] = useState(1);
    const [pageSize, setPageSize] = useState(PAGE_SIZE);

    const [agents, setAgents] = useState([]);
    const [summary, setSummary] = useState(EMPTY_SUMMARY);
    const [pagination, setPagination] = useState({
        current_page: 1,
        per_page: PAGE_SIZE,
        total: 0,
        last_page: 1,
        from: 0,
        to: 0,
    });
    const [options, setOptions] = useState(EMPTY_FILTERS);
    const [loading, setLoading] = useState(true);
    const [initialLoading, setInitialLoading] = useState(true);
    const [error, setError] = useState("");
    const [filterError, setFilterError] = useState("");
    const [filtersLoading, setFiltersLoading] = useState(true);
    const [refreshKey, setRefreshKey] = useState(0);
    const hasLoadedRef = useRef(false);

    useEffect(() => {
        const timer = setTimeout(() => setDebouncedSearch(search.trim()), 350);
        return () => clearTimeout(timer);
    }, [search]);

    useEffect(() => {
        const controller = new AbortController();
        setFiltersLoading(true);
        setFilterError("");
        const query = new URLSearchParams();
        if (state) query.set("state", state);
        const url = `${API_BASE}/filters${query.size ? `?${query}` : ""}`;
        getJSON(url, controller.signal)
            .then((data) => setOptions({ ...EMPTY_FILTERS, ...data }))
            .catch((err) => {
                if (err.name !== "AbortError") setFilterError(err.message);
            })
            .finally(() => {
                if (!controller.signal.aborted) setFiltersLoading(false);
            });
        return () => controller.abort();
    }, [state, refreshKey]);

    useEffect(() => {
        const controller = new AbortController();
        const params = new URLSearchParams({
            page: String(page),
            per_page: String(pageSize),
        });
        if (debouncedSearch) params.set("search", debouncedSearch);
        if (state) params.set("state", state);
        if (district) params.set("district", district);
        if (center) params.set("center_id", center);
        if (status) params.set("status", status);
        setLoading(true);
        setError("");
        getJSON(`${API_BASE}?${params}`, controller.signal)
            .then((data) => {
                const result = data.agents;
                if (!result || !Array.isArray(result.data))
                    throw new Error("Unexpected listing response.");
                setAgents(result.data);
                setSummary(data.summary ?? EMPTY_SUMMARY);
                setPagination({
                    current_page: Number(result.current_page ?? page),
                    per_page: Number(result.per_page ?? pageSize),
                    total: Number(result.total ?? 0),
                    last_page: Math.max(1, Number(result.last_page ?? 1)),
                    from: Number(result.from ?? 0),
                    to: Number(result.to ?? 0),
                });
                hasLoadedRef.current = true;
                setInitialLoading(false);
            })
            .catch((err) => {
                if (err.name !== "AbortError") {
                    setError(err.message);
                    setAgents([]);
                    setInitialLoading(false);
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [
        debouncedSearch,
        state,
        district,
        center,
        status,
        page,
        pageSize,
        refreshKey,
    ]);

    const resetPage = () => setPage(1);
    const clearFilters = () => {
        setSearch("");
        setDebouncedSearch("");
        setState("");
        setDistrict("");
        setCenter("");
        setStatus("");
        resetPage();
    };
    const retry = () => setRefreshKey((key) => key + 1);
    const changePage = useCallback(
        (next) => setPage(Math.max(1, Math.min(next, pagination.last_page))),
        [pagination.last_page],
    );
    const pageNumbers = useMemo(() => {
        const total = pagination.last_page;
        const current = pagination.current_page;
        const start = Math.max(1, Math.min(current - 2, total - 4));
        return Array.from(
            { length: Math.min(5, total - start + 1) },
            (_, i) => start + i,
        );
    }, [pagination.current_page, pagination.last_page]);
    const shownFrom = pagination.total
        ? pagination.from ||
          (pagination.current_page - 1) * pagination.per_page + 1
        : 0;
    const shownTo = pagination.total
        ? pagination.to ||
          Math.min(
              pagination.current_page * pagination.per_page,
              pagination.total,
          )
        : 0;
    const partnerName = options.partners?.[0]?.name ?? "Current Partner";

    return (
        <section className="fa-page" aria-busy={loading}>
            <div className="fa-page-heading">
                <div>
                    <span className="fa-eyebrow">
                        YOUTHHUB PARTNER / MANAGEMENT
                    </span>
                    <h2>Field Agent Listing</h2>
                    <p>
                        Manage field agents and monitor learner activity
                        performance.
                    </p>
                </div>
                <div className="fa-heading-actions">
                    <button
                        type="button"
                        className="fa-filter-btn"
                        onClick={retry}
                        title="Refresh listing"
                        disabled={loading}
                    >
                        <RefreshCw
                            size={16}
                            className={loading ? "fa-spin" : ""}
                        />{" "}
                        Refresh
                    </button>
                    <button
                        type="button"
                        className="fa-export-btn"
                        disabled
                        title="Full filtered CSV export endpoint is not implemented yet"
                    >
                        <Download size={17} /> Export Agents
                    </button>
                </div>
            </div>

            <div className="fa-summary-grid">
                <SummaryCard
                    title="Total Field Agents"
                    value={summary.total_agents}
                    icon={Users}
                    variant="agents"
                    description="Registered field agents"
                    loading={initialLoading}
                />

                <SummaryCard
                    title="Total Jobs / Events"
                    value={summary.total_jobs}
                    icon={CircleCheck}
                    variant="jobs"
                    description="Combined jobs and events"
                    loading={initialLoading}
                />

                <SummaryCard
                    title="Total Accepted"
                    value={summary.total_accepted}
                    icon={BriefcaseBusiness}
                    variant="accepted"
                    description="Accepted transactions"
                    loading={initialLoading}
                />

                <SummaryCard
                    title="Action Required"
                    value={summary.action_required}
                    icon={AlertCircle}
                    variant="required"
                    description="Awaiting action"
                    loading={initialLoading}
                />
            </div>

            <div className="fa-listing-panel">
                <div className="fa-filter-toolbar">
                    <div className="fa-search-box">
                        <Search size={18} />
                        <input
                            type="search"
                            value={search}
                            placeholder="Search name, agent ID, mobile or email..."
                            onChange={(e) => {
                                setSearch(e.target.value);
                                resetPage();
                            }}
                            aria-label="Search field agents"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => {
                                    setSearch("");
                                    resetPage();
                                }}
                                aria-label="Clear search"
                            >
                                <X size={15} />
                            </button>
                        )}
                    </div>
                    <div className="fa-select-wrap">
                        <MapPin size={16} />
                        <select
                            value={state}
                            disabled={filtersLoading && !options.states.length}
                            onChange={(e) => {
                                setState(e.target.value);
                                setDistrict("");
                                resetPage();
                            }}
                            aria-label="Filter by state"
                        >
                            <option value="">All States</option>
                            {options.states.map((item) => (
                                <option key={item} value={item}>
                                    {item}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="fa-select-wrap">
                        <MapPin size={16} />
                        <select
                            value={district}
                            disabled={filtersLoading}
                            onChange={(e) => {
                                setDistrict(e.target.value);
                                resetPage();
                            }}
                            aria-label="Filter by district"
                        >
                            <option value="">All Districts</option>
                            {options.districts.map((item) => (
                                <option key={item} value={item}>
                                    {item}
                                </option>
                            ))}
                        </select>
                    </div>
                    <button
                        type="button"
                        className={`fa-filter-btn ${showFilters ? "active" : ""}`}
                        onClick={() => setShowFilters((v) => !v)}
                    >
                        <SlidersHorizontal size={17} /> Filters
                    </button>
                </div>
                {filterError && (
                    <div className="fa-inline-error" role="alert">
                        Unable to load filter options: {filterError}{" "}
                        <button type="button" onClick={retry}>
                            Retry
                        </button>
                    </div>
                )}
                {showFilters && (
                    <div className="fa-advanced-filters">
                        <div className="fa-filter-field">
                            <label htmlFor="fa-partner">Partner</label>
                            <select
                                id="fa-partner"
                                value={options.partners?.[0]?.id ?? ""}
                                disabled
                            >
                                <option value={options.partners?.[0]?.id ?? ""}>
                                    {partnerName}
                                </option>
                            </select>
                        </div>
                        <div className="fa-filter-field">
                            <label htmlFor="fa-center">Partner Center</label>
                            <select
                                id="fa-center"
                                value={center}
                                disabled={filtersLoading}
                                onChange={(e) => {
                                    setCenter(e.target.value);
                                    resetPage();
                                }}
                            >
                                <option value="">All Centers</option>
                                {options.centers.map((item) => (
                                    <option
                                        key={item.id}
                                        value={String(item.id)}
                                    >
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="fa-filter-field">
                            <label htmlFor="fa-status">Agent Status</label>
                            <select
                                id="fa-status"
                                value={status}
                                onChange={(e) => {
                                    setStatus(e.target.value);
                                    resetPage();
                                }}
                            >
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <button
                            type="button"
                            className="fa-clear-btn"
                            onClick={clearFilters}
                        >
                            <X size={15} /> Clear Filters
                        </button>
                    </div>
                )}
                <div className="fa-table-meta">
                    <div className="fa-legend">
                        {STATUS.map((item) => (
                            <div key={item.label} className="fa-legend-item">
                                <span
                                    className={`fa-legend-dot ${item.className}`}
                                />
                                <span>{item.label}</span>
                            </div>
                        ))}
                    </div>
                    <span className="fa-result-count">
                        {loading ? (
                            "Loading results..."
                        ) : (
                            <>
                                Showing <strong>{shownFrom}</strong> -{" "}
                                <strong>{shownTo}</strong> of{" "}
                                <strong>{pagination.total}</strong> results
                            </>
                        )}
                    </span>
                </div>
                <div className="fa-table-scroll">
                    <table className="fa-table">
                        <thead>
                            <tr>
                                <th>SN</th>
                                <th>Field Agent</th>
                                <th>Contact Details</th>
                                <th>Partner / Center</th>
                                <th>Total Jobs / Events</th>
                                <th>Social Protection</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <TableSkeleton rows={Math.min(pageSize, 10)} />
                            ) : error ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="fa-empty" role="alert">
                                            <AlertCircle size={30} />
                                            <strong>
                                                Unable to load field agents
                                            </strong>
                                            <p>{error}</p>
                                            <button
                                                type="button"
                                                onClick={retry}
                                            >
                                                Try Again
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ) : agents.length ? (
                                agents.map((agent, index) => (
                                    <tr key={agent.id}>
                                        <td
                                            className="fa-sn"
                                            onClick={() =>
                                                onViewDetails?.(agent.id)
                                            }
                                        >
                                            {shownFrom + index}
                                        </td>
                                        <td
                                            onClick={() =>
                                                onViewDetails?.(agent.id)
                                            }
                                        >
                                            <div className="fa-agent-cell">
                                                <div className="fa-avatar">
                                                    {String(agent.name || "?")
                                                        .trim()
                                                        .split(/\s+/)
                                                        .slice(0, 2)
                                                        .map((n) => n[0])
                                                        .join("")
                                                        .toUpperCase()}
                                                </div>
                                                <div>
                                                    <strong>
                                                        {agent.name || "—"}
                                                    </strong>
                                                    <small>
                                                        {agent.agentId || "—"}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div className="fa-contact-cell">
                                                <span>
                                                    <Phone size={12} />
                                                    {agent.mobile || "—"}
                                                </span>
                                                <span title={agent.email || ""}>
                                                    <Mail size={12} />
                                                    {agent.email || "—"}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div className="fa-partner-cell">
                                                <strong>
                                                    {agent.partner || "—"}
                                                </strong>
                                                <small>
                                                    {agent.center || "—"}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <StatusCounters
                                                values={agent.jobs}
                                            />
                                        </td>
                                        <td>
                                            <StatusCounters
                                                values={agent.social}
                                            />
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                className="fa-view-btn"
                                                title="View Agent Details"
                                                onClick={() =>
                                                    onViewDetails?.(agent.id)
                                                }
                                            >
                                                <Eye size={15} />
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="fa-empty">
                                            <Users size={30} />
                                            <strong>
                                                No field agents found
                                            </strong>
                                            <p>
                                                Try changing your search or
                                                filter criteria.
                                            </p>
                                            <button
                                                type="button"
                                                onClick={clearFilters}
                                            >
                                                Clear Filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="fa-pagination">
                    <div className="fa-page-size">
                        <span>Rows per page:</span>
                        <select
                            value={pageSize}
                            disabled={loading}
                            onChange={(e) => {
                                setPageSize(Number(e.target.value));
                                setPage(1);
                            }}
                        >
                            <option value={10}>10</option>
                            <option value={20}>20</option>
                            <option value={50}>50</option>
                        </select>
                    </div>
                    <div className="fa-page-controls">
                        <button
                            type="button"
                            disabled={loading || pagination.current_page <= 1}
                            onClick={() =>
                                changePage(pagination.current_page - 1)
                            }
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>
                        {pageNumbers.map((n) => (
                            <button
                                type="button"
                                key={n}
                                disabled={loading}
                                className={
                                    pagination.current_page === n
                                        ? "active"
                                        : ""
                                }
                                onClick={() => changePage(n)}
                            >
                                {n}
                            </button>
                        ))}
                        <button
                            type="button"
                            disabled={
                                loading ||
                                pagination.current_page >= pagination.last_page
                            }
                            onClick={() =>
                                changePage(pagination.current_page + 1)
                            }
                            aria-label="Next page"
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </div>
            </div>
        </section>
    );
}
