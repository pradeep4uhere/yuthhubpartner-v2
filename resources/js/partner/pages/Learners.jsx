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

const PAGE_SIZE = 10;

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
                }
            }
        }

        loadLearners();

        return () => controller.abort();
    }, [page, search, status, agent, refreshKey]);

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

    return (
        <main className="partner-learners">
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

            <section className="pl-summary">
                <article className="pl-summary-card">
                    <div className="pl-summary-card__top">
                        <span>Total Learners</span>
                        <span className="pl-summary-icon pl-summary-icon--blue">
                            <Users size={20} />
                        </span>
                    </div>
                    <strong>{formatNumber(summary.total)}</strong>
                    <span className="pl-summary-caption">
                        Registered learners
                    </span>
                </article>

                <article className="pl-summary-card">
                    <div className="pl-summary-card__top">
                        <span>Registered This Month</span>
                        <span className="pl-summary-icon pl-summary-icon--green">
                            <UserPlus size={20} />
                        </span>
                    </div>
                    <strong>{formatNumber(summary.thisMonth)}</strong>
                    <span className="pl-summary-caption">
                        New learner registrations
                    </span>
                </article>

                <article className="pl-summary-card">
                    <div className="pl-summary-card__top">
                        <span>Field Agents</span>
                        <span className="pl-summary-icon pl-summary-icon--purple">
                            <GraduationCap size={20} />
                        </span>
                    </div>
                    <strong>{formatNumber(summary.fieldAgents)}</strong>
                    <span className="pl-summary-caption">
                        Agents with learner records
                    </span>
                </article>
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

                    <label className="pl-select-wrap">
                        <SlidersHorizontal size={16} />

                        <select
                            value={status}
                            onChange={(event) => {
                                setStatus(event.target.value);
                                setPage(1);
                            }}
                            aria-label="Filter by registration status"
                        >
                            <option value="all">All statuses</option>
                            <option value="registered">Registered</option>
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </label>

                    <label className="pl-select-wrap">
                        <Users size={16} />

                        <select
                            value={agent}
                            onChange={(event) => {
                                setAgent(event.target.value);
                                setPage(1);
                            }}
                            aria-label="Filter by field agent"
                        >
                            <option value="all">All field agents</option>

                            {allAgents.map((name) => (
                                <option value={name} key={name}>
                                    {name}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>

                {(search || status !== "all" || agent !== "all") && (
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

                <div className="pl-table-scroll">
                    <table className="pl-table">
                        <thead>
                            <tr>
                                <th>Learner</th>
                                <th>Registration Date</th>
                                <th>Mobile Number</th>
                                <th>Field Agent</th>
                                <th>Registered On</th>
                                <th>Status</th>
                                <th aria-label="Actions" />
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan={7} className="pl-empty">
                                        <RefreshCw
                                            size={20}
                                            className="pl-spin"
                                        />
                                        Loading learner records...
                                    </td>
                                </tr>
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
                                                <span className="pl-reg-id">
                                                    {learner.source_created_at ||
                                                        "—"}
                                                </span>
                                            </td>

                                            <td>
                                                {learner.phone_number || "—"}
                                            </td>
                                            <td>
                                                {learner.field_agent || "—"}
                                            </td>

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
                                                    className={`pl-status pl-status--${safeStatus}`}
                                                >
                                                    <span />
                                                    {learner.status ||
                                                        "Unknown"}
                                                </span>
                                            </td>

                                            <td>
                                                <button
                                                    type="button"
                                                    className="pl-icon-button"
                                                    title="More actions"
                                                    aria-label={`More actions for ${fullName}`}
                                                    onClick={() =>
                                                        window.alert(
                                                            "Learner details and actions can be connected here.",
                                                        )
                                                    }
                                                >
                                                    <MoreHorizontal size={19} />
                                                </button>
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
