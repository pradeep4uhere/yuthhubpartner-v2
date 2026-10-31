import { useEffect, useMemo, useState } from "react";
import {
    Search,
    Users,
    UserPlus,
    Download,
    SlidersHorizontal,
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    RefreshCw,
    CalendarDays,
    Check,
    X,
} from "lucide-react";
import "../../../partner/pages/PartnerLearnerList.css";
import LearnerStatusDots from "../../../field-agent/pages/LearnerStatusDots";
import LearnerStatusLegend from "../../LearnerStatusLegend";

const PAGE_SIZE = 10;
const API = "/api/partner-placement/learners";
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
const formatNumber = (value) => Number(value ?? 0).toLocaleString("en-IN");
const formatDate = (date) => {
    if (!date) return "—";
    const parsed = new Date(date);
    return Number.isNaN(parsed.getTime())
        ? "—"
        : parsed.toLocaleDateString("en-IN", {
              day: "2-digit",
              month: "short",
              year: "numeric",
          });
};
const initials = (name = "") =>
    name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0] || "")
        .join("")
        .toUpperCase() || "L";
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
                    <Check size={22} strokeWidth={2.5} />
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
function SummaryCard({ color, Icon, label, value, caption }) {
    return (
        <article className={`pl-summary-card pl-summary-card--${color}`}>
            <span className="pl-summary-icon">
                <Icon size={20} />
            </span>
            <div className="pl-summary-content">
                <span className="pl-summary-label">{label}</span>
                <strong>{formatNumber(value)}</strong>
                <span className="pl-summary-caption">{caption}</span>
            </div>
            <span className="pl-summary-arrow">
                <ChevronRight size={17} />
            </span>
        </article>
    );
}
export default function Learners() {
    const [exportToast, setExportToast] = useState(null);
    const [showAdvanced, setShowAdvanced] = useState(false);
    const [advancedDraft, setAdvancedDraft] = useState({
        ...EMPTY_ADVANCED_FILTERS,
    });
    const [advancedFilters, setAdvancedFilters] = useState({
        ...EMPTY_ADVANCED_FILTERS,
    });
    const [learners, setLearners] = useState([]);
    const [summary, setSummary] = useState({
        total: 0,
        thisMonth: 0,
        certificationCompleted: 0,
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
    const updateAdvanced = (field, value) =>
        setAdvancedDraft((previous) => ({ ...previous, [field]: value }));
    const applyAdvancedFilters = () => {
        setPage(1);
        setAdvancedFilters({ ...advancedDraft });
    };
    const clearAdvancedFilters = () => {
        setAdvancedDraft({ ...EMPTY_ADVANCED_FILTERS });
        setAdvancedFilters({ ...EMPTY_ADVANCED_FILTERS });
        setPage(1);
    };
    const buildParams = (includePage = true) => {
        const params = new URLSearchParams({
            search: search.trim(),
            status,
            agent,
        });
        if (includePage) {
            params.set("page", String(page));
            params.set("per_page", String(PAGE_SIZE));
        }
        Object.entries(advancedFilters).forEach(([key, value]) => {
            const normalized = String(value ?? "").trim();
            if (normalized !== "") params.set(key, normalized);
        });
        return params;
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
                    if (normalized) params.set(key, normalized);
                });
                const response = await fetch(`${API}?${params.toString()}`, {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                    signal: controller.signal,
                });
                if (!response.ok)
                    throw new Error(
                        `Unable to load learners (HTTP ${response.status}).`,
                    );
                const result = await response.json();
                if (result.success !== true || !Array.isArray(result.data))
                    throw new Error(
                        result.message ||
                            "Unable to load learner records. Check API response format.",
                    );
                if (controller.signal.aborted) return;
                setLearners(result.data);
                setSummary({
                    total: Number(result.summary?.total ?? 0),
                    thisMonth: Number(result.summary?.this_month ?? 0),
                    certificationCompleted: Number(
                        result.summary?.certification_completed ??
                            result.summary?.field_agents ??
                            0,
                    ),
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
                if (Array.isArray(result.agents)) setAllAgents(result.agents);
            } catch (err) {
                if (err.name !== "AbortError" && !controller.signal.aborted) {
                    setError(err.message || "Something went wrong.");
                    setLearners([]);
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
    const hasActiveFilters =
        search.trim() !== "" ||
        status !== "all" ||
        agent !== "all" ||
        Object.values(advancedFilters).some(
            (value) => String(value ?? "").trim() !== "",
        );
    const exportCsv = async () => {
        if (exporting) return;
        setExporting(true);
        setError("");
        try {
            const response = await fetch(`${API}/export`, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    filters: Object.fromEntries(buildParams(false).entries()),
                }),
            });
            const result = await response.json();
            if (!response.ok || result.success === false)
                throw new Error(
                    result.message || "Unable to create learner export.",
                );
            const exportId =
                result.export?.id ??
                result.data?.export?.id ??
                result.data?.id ??
                result.export_id ??
                result.id;
            if (!exportId)
                throw new Error(
                    "Export response mein export ID nahi mila. API response check karein.",
                );
            setExportToast({
                type: "success",
                title: "Export Request Submitted",
                message:
                    "Your learner export is being prepared. You can download it once processing is complete.",
                exportId,
                key: Date.now(),
            });
        } catch (err) {
            setError(err.message || "Unable to export learners.");
        } finally {
            setExporting(false);
        }
    };
    return (
        <main className="partner-learners pl-page-loading-wrapper">
            <header className="pl-page-header">
                <div>
                    <div className="pl-breadcrumb">
                        Placement Partner Portal <span>/</span> Learners
                    </div>
                    <h1>Learner List</h1>
                    <p>
                        Manage and track learners registered under your
                        associated Field Agents.
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
                    {exporting
                        ? "Requesting Export..."
                        : "Export All Matching Learners"}
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
                        <SummaryCard
                            color="blue"
                            Icon={Users}
                            label="Total Learners"
                            value={summary.total}
                            caption="Registered learners"
                        />
                        <SummaryCard
                            color="green"
                            Icon={UserPlus}
                            label="Registered This Month"
                            value={summary.thisMonth}
                            caption="New learner registrations"
                        />
                        <SummaryCard
                            color="purple"
                            Icon={GraduationCap}
                            label="Certification Completed"
                            value={summary.certificationCompleted}
                            caption="Certification completion records"
                        />
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
                        disabled={loading || exporting}
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
                            Advanced Search {showAdvanced ? " −" : " +"}
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
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <LearnerTableSkeleton rows={PAGE_SIZE} />
                            ) : error ? (
                                <tr>
                                    <td colSpan={6} className="pl-empty">
                                        Learner records could not be loaded.
                                    </td>
                                </tr>
                            ) : visibleLearners.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="pl-empty">
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
                                                    className={`pl-status ${Number(learner.course_completed) === 1 ? "pl-status--active" : "pl-status--inactive"}`}
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
            <ExportToast
                toast={exportToast}
                onClose={() => setExportToast(null)}
            />
        </main>
    );
}
