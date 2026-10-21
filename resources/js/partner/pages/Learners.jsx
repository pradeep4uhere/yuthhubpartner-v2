import { useEffect, useMemo, useState } from "react";
import {
    Search,
    Users,
    UserPlus,
    Download,
    SlidersHorizontal,
    ChevronLeft,
    ChevronRight,
    MoreHorizontal,
    GraduationCap,
    RefreshCw,
    CalendarDays,
    X,
} from "lucide-react";

import "./PartnerLearnerList.css";
import LearnerStatusDots from "../../field-agent/pages/LearnerStatusDots";
import LearnerStatusLegend from "../../components/LearnerStatusLegend";

const PAGE_SIZE = 10;
/* =========================================
   LEARNER LIST SKELETON COMPONENTS
========================================= */

const Skeleton = ({ width, height, circle = false, className = "" }) => (
    <span
        className={`pl-skeleton ${circle ? "pl-skeleton--circle" : ""} ${className}`}
        style={{ width, height }}
        aria-hidden="true"
    />
);

const SummarySkeleton = ({ color }) => (
    <article className={`pl-summary-card pl-summary-card--${color}`}>
        <Skeleton width={42} height={42} circle />

        <div className="pl-summary-content">
            <Skeleton width="105px" height={11} />
            <Skeleton width="85px" height={25} />
            <Skeleton width="135px" height={10} />
        </div>

        <Skeleton width={24} height={24} circle />
    </article>
);

const LearnerTableSkeleton = ({ rows = PAGE_SIZE }) => (
    <>
        {Array.from({ length: rows }, (_, index) => (
            <tr className="pl-skeleton-row" key={index}>
                <td>
                    <div className="pl-learner">
                        <Skeleton width={36} height={36} circle />

                        <div className="pl-skeleton-stack">
                            <Skeleton width={125} height={12} />
                            <Skeleton width={75} height={9} />
                        </div>
                    </div>
                </td>

                <td>
                    <Skeleton width={110} height={12} />
                </td>

                <td>
                    <Skeleton width={105} height={12} />
                </td>

                <td>
                    <Skeleton width={100} height={12} />
                </td>

                <td>
                    <Skeleton width={95} height={12} />
                </td>

                <td>
                    <Skeleton
                        width={62}
                        height={24}
                        className="pl-skeleton-pill"
                    />
                </td>
            </tr>
        ))}
    </>
);

const EMPTY_ADVANCED_FILTERS = {
    name: "",
    mobile: "",
    email: "",
    unit: "",
    state: "",
    city: "",
    digital: "",
    english: "",
};

const ADVANCED_SEARCH_FIELDS = [
    { key: "name", label: "Learner Name", placeholder: "Search full name" },
    {
        key: "mobile",
        label: "Mobile Number",
        placeholder: "Enter mobile number",
    },
    {
        key: "email",
        label: "Email Address",
        placeholder: "Enter email address",
    },
    { key: "unit", label: "Unit / Institute", placeholder: "Enter unit" },
    { key: "state", label: "State", placeholder: "Enter state" },
    { key: "city", label: "City", placeholder: "Enter city" },
    {
        key: "digital",
        label: "Digital Proficiency",
        placeholder: "Enter proficiency",
    },
    {
        key: "english",
        label: "English Proficiency",
        placeholder: "Enter English level",
    },
];

const formatDate = (date) => {
    if (!date) return "—";

    const parsed = new Date(date);

    if (Number.isNaN(parsed.getTime())) return "—";

    return parsed.toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const formatNumber = (value) => Number(value ?? 0).toLocaleString("en-IN");

const initials = (name = "") =>
    name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0] || "")
        .join("")
        .toUpperCase() || "L";

const escapeCsv = (value) => `"${String(value ?? "").replace(/"/g, '""')}"`;

export default function PartnerLearnerList() {
    // Advanced Search Panel
    const [showAdvanced, setShowAdvanced] = useState(false);

    // Values currently entered by the user
    const [advancedDraft, setAdvancedDraft] = useState({
        ...EMPTY_ADVANCED_FILTERS,
    });

    // Values actually applied to the API
    const [advancedFilters, setAdvancedFilters] = useState({
        ...EMPTY_ADVANCED_FILTERS,
    });

    const updateAdvanced = (field, value) => {
        setAdvancedDraft((previous) => ({
            ...previous,
            [field]: value,
        }));
    };

    const [learners, setLearners] = useState([]);

    const [summary, setSummary] = useState({
        total: 0,
        thisMonth: 0,
        fieldAgents: 0,
    });

    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [agent, setAgent] = useState("all");
    const [page, setPage] = useState(1);

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

    const [allAgents, setAllAgents] = useState([]);

    const applyAdvancedFilters = () => {
        setPage(1);
        setAdvancedFilters({ ...advancedDraft });
    };

    const clearAdvancedFilters = () => {
        setAdvancedDraft({ ...EMPTY_ADVANCED_FILTERS });
        setAdvancedFilters({ ...EMPTY_ADVANCED_FILTERS });
        setPage(1);
    };

    useEffect(() => {
        const controller = new AbortController();

        async function loadLearners() {
            setLoading(true);
            setError("");

            try {
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(PAGE_SIZE),
                    search: search.trim(),
                    status,
                    agent,
                });

                Object.entries(advancedFilters).forEach(([key, value]) => {
                    const normalized = String(value ?? "").trim();

                    if (normalized !== "") {
                        params.set(key, normalized);
                    }
                });

                const response = await fetch(
                    `/api/partner/learners?${params.toString()}`,
                    {
                        headers: {
                            Accept: "application/json",
                        },
                        credentials: "same-origin",
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error(
                        `Unable to load learners (HTTP ${response.status}).`,
                    );
                }

                const result = await response.json();

                if (result.success !== true) {
                    throw new Error(
                        result.message || "Unable to load learner records.",
                    );
                }

                setLearners(Array.isArray(result.data) ? result.data : []);

                setSummary({
                    total: Number(result.summary?.total ?? 0),
                    thisMonth: Number(result.summary?.this_month ?? 0),
                    fieldAgents: Number(result.summary?.field_agents ?? 0),
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

                if (Array.isArray(result.agents)) {
                    setAllAgents(result.agents);
                } else if (
                    page === 1 &&
                    !search &&
                    status === "all" &&
                    agent === "all"
                ) {
                    setAllAgents((previous) => {
                        const names = result.data
                            .map((learner) => learner.field_agent)
                            .filter(Boolean);

                        return [...new Set([...previous, ...names])].sort(
                            (a, b) => a.localeCompare(b),
                        );
                    });
                }
            } catch (err) {
                if (err.name !== "AbortError") {
                    setError(err.message || "Something went wrong.");
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                    setInitialLoading(false);
                }
            }
        }

        loadLearners();

        return () => controller.abort();
    }, [page, search, status, agent, advancedFilters, refreshKey]);

    const currentPage = pagination.current_page;
    const totalPages = pagination.last_page;

    const startRecord = pagination.total
        ? (currentPage - 1) * pagination.per_page + 1
        : 0;

    const endRecord = Math.min(
        (currentPage - 1) * pagination.per_page + learners.length,
        pagination.total,
    );

    const visibleLearners = useMemo(() => learners, [learners]);

    const resetFilters = () => {
        setSearch("");
        setStatus("all");
        setAgent("all");
        setAdvancedDraft({ ...EMPTY_ADVANCED_FILTERS });
        setAdvancedFilters({ ...EMPTY_ADVANCED_FILTERS });
        setPage(1);
    };

    const exportCsv = async () => {
        // This exports the currently loaded page.
        // Use a dedicated backend export endpoint for all matching records.
        if (!visibleLearners.length) return;

        setExporting(true);

        try {
            const columns = [
                ["Learner", "full_name"],
                ["Registration Date", "source_created_at"],
                ["Mobile", "phone_number"],
                ["Field Agent", "field_agent"],
                ["Unit", "unit"],
                ["Registered On", "created_at"],
                ["Status", "status"],
            ];

            const rows = visibleLearners.map((learner) => {
                const fullName = [learner.first_name, learner.last_name]
                    .filter(Boolean)
                    .join(" ");

                return columns
                    .map(([, key]) =>
                        escapeCsv(
                            key === "full_name" ? fullName : learner[key],
                        ),
                    )
                    .join(",");
            });

            const csv = [
                columns.map(([label]) => escapeCsv(label)).join(","),
                ...rows,
            ].join("\r\n");

            const blobUrl = URL.createObjectURL(
                new Blob(["\uFEFF" + csv], {
                    type: "text/csv;charset=utf-8;",
                }),
            );

            const link = document.createElement("a");
            link.href = blobUrl;
            link.download = `partner-learners-page-${currentPage}.csv`;
            document.body.appendChild(link);
            link.click();
            link.remove();

            URL.revokeObjectURL(blobUrl);
        } finally {
            setExporting(false);
        }
    };

    const hasActiveFilters =
        search.trim() !== "" ||
        status !== "all" ||
        agent !== "all" ||
        Object.values(advancedFilters).some(
            (value) => String(value ?? "").trim() !== "",
        );

    return (
        <main className="partner-learners pl-page-loading-wrapper">
            <header className="pl-page-header">
                <div>
                    <div className="pl-breadcrumb">
                        Partner Portal <span>/</span> Learners
                    </div>

                    <h1>Learner List</h1>
                    <p>
                        Manage and track your registered learners in one place.
                    </p>
                </div>

                <button
                    type="button"
                    className="pl-button pl-button--primary"
                    onClick={exportCsv}
                    disabled={!visibleLearners.length || loading || exporting}
                >
                    {exporting ? (
                        <RefreshCw size={17} className="pl-spin" />
                    ) : (
                        <Download size={17} />
                    )}
                    {exporting ? "Exporting..." : "Export Current Page"}
                </button>
            </header>

            <section className="pl-summary" aria-busy={initialLoading}>
                {initialLoading ? (
                    <>
                        <SummarySkeleton color="blue" />
                        <SummarySkeleton color="green" />
                        <SummarySkeleton color="purple" />
                    </>
                ) : (
                    <>
                        {/* Total Learners */}
                        <article className="pl-summary-card pl-summary-card--blue">
                            <span className="pl-summary-icon">
                                <Users size={20} />
                            </span>

                            <div className="pl-summary-content">
                                <span className="pl-summary-label">
                                    Total Learners
                                </span>

                                <strong>{formatNumber(summary.total)}</strong>

                                <span className="pl-summary-caption">
                                    Registered learners
                                </span>
                            </div>

                            <span className="pl-summary-arrow">
                                <ChevronRight size={17} />
                            </span>
                        </article>

                        {/* Registered This Month */}
                        <article className="pl-summary-card pl-summary-card--green">
                            <span className="pl-summary-icon">
                                <UserPlus size={20} />
                            </span>

                            <div className="pl-summary-content">
                                <span className="pl-summary-label">
                                    Registered This Month
                                </span>

                                <strong>
                                    {formatNumber(summary.thisMonth)}
                                </strong>

                                <span className="pl-summary-caption">
                                    New learner registrations
                                </span>
                            </div>

                            <span className="pl-summary-arrow">
                                <ChevronRight size={17} />
                            </span>
                        </article>

                        {/* Certification Completed */}
                        <article className="pl-summary-card pl-summary-card--purple">
                            <span className="pl-summary-icon">
                                <GraduationCap size={20} />
                            </span>

                            <div className="pl-summary-content">
                                <span className="pl-summary-label">
                                    Certification Completed
                                </span>

                                <strong>
                                    {formatNumber(summary.fieldAgents)}
                                </strong>

                                <span className="pl-summary-caption">
                                    Certification Completion records
                                </span>
                            </div>

                            <span className="pl-summary-arrow">
                                <ChevronRight size={17} />
                            </span>
                        </article>
                    </>
                )}
            </section>

            <section className="pl-table-card">
                <div className="pl-table-heading">
                    <div>
                        <h2>All Learners</h2>
                        <p>Search, filter and view your learner records.</p>
                    </div>

                    <button
                        type="button"
                        className="pl-button pl-button--secondary"
                        onClick={() => setRefreshKey((key) => key + 1)}
                        disabled={loading}
                    >
                        <RefreshCw
                            size={15}
                            className={loading ? "pl-spin" : ""}
                        />
                        Refresh
                    </button>
                </div>

                <div className="pl-filters">
                    <label className="pl-search">
                        <Search size={18} />

                        <input
                            type="search"
                            placeholder="Search name, ID or mobile..."
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
                            onClick={() => setShowAdvanced((value) => !value)}
                        >
                            <SlidersHorizontal size={16} />
                            Advanced Search
                            {showAdvanced ? " −" : " +"}
                        </button>
                    </div>
                </div>

                {showAdvanced && (
                    <div className="pl-advanced-panel">
                        <div className="pl-advanced-heading">
                            <div>
                                <h3>Advanced Learner Search</h3>
                                <p>
                                    Combine multiple filters to find matching
                                    learners.
                                </p>
                            </div>
                        </div>
                        <div className="pl-advanced-grid">
                            {ADVANCED_SEARCH_FIELDS.map((field) => (
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
                        </div>

                        <div className="pl-advanced-actions">
                            {/* Left: Colorful Result Count */}
                            <div className="pl-result-count">
                                <div className="pl-result-count__icon">
                                    <Users size={20} />
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
                                            ? "Searching learners..."
                                            : "Matching learner records"}
                                    </span>
                                </div>

                                <span className="pl-result-count__arrow">
                                    <ChevronRight size={17} />
                                </span>
                            </div>

                            {/* Right: Action Buttons */}
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

                {error && (
                    <div className="pl-error" role="alert">
                        <span>{error}</span>
                        <button
                            type="button"
                            onClick={() => setRefreshKey((key) => key + 1)}
                        >
                            Try again
                        </button>
                    </div>
                )}
                <LearnerStatusLegend />
                <div className="pl-table-scroll" aria-busy={loading}>
                    <table className="pl-table">
                        <thead>
                            <tr>
                                <th>Learner</th>

                                <th>Mobile Number</th>
                                <th>Field Agent</th>
                                <th>Registered On</th>
                                <th>Certification</th>
                                <th>Status</th>
                                {/* <th aria-label="Actions" /> */}
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                <LearnerTableSkeleton rows={PAGE_SIZE} />
                            ) : error ? (
                                <tr>
                                    <td colSpan={7} className="pl-empty">
                                        Learner records could not be loaded.
                                    </td>
                                </tr>
                            ) : visibleLearners.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="pl-empty">
                                        <Users size={26} />
                                        <strong>No learners found</strong>
                                        <span>
                                            Try changing your search or filters.
                                        </span>
                                    </td>
                                </tr>
                            ) : (
                                visibleLearners.map((learner, index) => {
                                    const fullName =
                                        [learner.first_name, learner.last_name]
                                            .filter(Boolean)
                                            .join(" ") || "—";

                                    const safeStatus = String(
                                        learner.status || "unknown",
                                    )
                                        .toLowerCase()
                                        .replace(/[^a-z0-9_-]/g, "");

                                    return (
                                        <tr key={learner.id ?? index}>
                                            <td>
                                                <div className="pl-learner">
                                                    <span className="pl-avatar">
                                                        {initials(fullName)}
                                                    </span>
                                                    <strong>{fullName}</strong>
                                                </div>
                                            </td>

                                            <td>
                                                {learner.phone_number || "—"}
                                            </td>
                                            <td>{learner.unit || "—"}</td>

                                            <td>
                                                <span className="pl-date">
                                                    <CalendarDays size={14} />
                                                    {formatDate(
                                                        learner.created_at,
                                                    )}
                                                </span>
                                            </td>

                                            <td>
                                                <span
                                                    className={`pl-status ${
                                                        Number(
                                                            learner.course_completed,
                                                        ) === 1
                                                            ? "pl-status--active"
                                                            : "pl-status--inactive"
                                                    }`}
                                                >
                                                    <span />
                                                    {Number(
                                                        learner.course_completed,
                                                    ) === 1
                                                        ? "Yes"
                                                        : "No"}
                                                </span>
                                            </td>

                                            <td>
                                                <LearnerStatusDots
                                                    learner={learner}
                                                />
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>

                <footer className="pl-pagination">
                    <span>
                        Showing {startRecord}–{endRecord} of{" "}
                        {formatNumber(pagination.total)} learners
                    </span>

                    <div className="pl-pagination-controls">
                        <button
                            type="button"
                            aria-label="Previous page"
                            disabled={currentPage <= 1 || loading}
                            onClick={() =>
                                setPage((value) => Math.max(1, value - 1))
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
                                setPage((value) =>
                                    Math.min(totalPages, value + 1),
                                )
                            }
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </footer>
            </section>
        </main>
    );
}
