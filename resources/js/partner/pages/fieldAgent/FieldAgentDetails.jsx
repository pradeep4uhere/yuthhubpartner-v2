import { useEffect, useMemo, useState } from "react";
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
} from "lucide-react";
import "./FieldAgentDetails.css";
import EventTransactionDrawer from "../../pages/event/EventTransactionDrawer";

const API = "/api/partner/field-agents";
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
const fmt = (value) =>
    value === null || value === undefined || value === "" ? "—" : String(value);
const date = (value) =>
    value
        ? new Date(value).toLocaleDateString("en-IN", {
              day: "2-digit",
              month: "short",
              year: "numeric",
          })
        : "—";
const Skeleton = ({ width = "100%" }) => (
    <span className="fad-skeleton" style={{ width }} />
);
const getAgentId = () => {
    const match = window.location.pathname.match(/\/field-agents\/(\d+)\/?$/);
    return match ? match[1] : null;
};
export default function FieldAgentDetails({ agentId = getAgentId(), onBack }) {
    const [search, setSearch] = useState("");
    const [debounced, setDebounced] = useState("");
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(25);
    const [reload, setReload] = useState(0);
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [hidden, setHidden] = useState([]);
    const [showColumns, setShowColumns] = useState(false);
    const [selectedTransactionId, setSelectedTransactionId] = useState(null);

    const openTransaction = (id) => {
        setSelectedTransactionId(id);
    };

    const closeTransaction = () => {
        setSelectedTransactionId(null);
    };

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(search.trim()), 350);
        return () => clearTimeout(timer);
    }, [search]);
    useEffect(() => {
        if (!agentId) {
            setError("Invalid field agent ID.");
            setLoading(false);
            return;
        }
        const controller = new AbortController();
        const params = new URLSearchParams({
            page: String(page),
            per_page: String(perPage),
        });
        if (debounced) params.set("search", debounced);
        if (status) params.set("status", status);
        setLoading(true);
        setError("");
        fetch(`${API}/${encodeURIComponent(agentId)}/transactions?${params}`, {
            credentials: "same-origin",
            headers: { Accept: "application/json" },
            signal: controller.signal,
        })
            .then(async (response) => {
                const body = await response.json();
                if (!response.ok || !body.success)
                    throw new Error(
                        body.message || "Unable to load transactions",
                    );
                return body.data;
            })
            .then((data) => setResult(data))
            .catch((err) => {
                if (err.name !== "AbortError") setError(err.message);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [agentId, debounced, status, page, perPage, reload]);
    const pagination = result?.transactions;
    const records = pagination?.data ?? [];
    const visible = useMemo(
        () => COLUMNS.filter(([key]) => !hidden.includes(key)),
        [hidden],
    );
    const back = () =>
        onBack ? onBack() : window.location.assign("/partner/field-agents");
    const updateSearch = (value) => {
        setSearch(value);
        setPage(1);
    };
    const updateStatus = (value) => {
        setStatus(value);
        setPage(1);
    };
    const start = pagination?.total
        ? (pagination.current_page - 1) * pagination.per_page + 1
        : 0;
    const end = pagination?.total
        ? Math.min(
              pagination.total,
              pagination.current_page * pagination.per_page,
          )
        : 0;
    const renderCell = (row, key) => {
        if (key === "id") {
            return (
                <span
                    className="fad-event-id-link"
                    role="button"
                    tabIndex={0}
                    onClick={() => openTransaction(row.id)}
                    onKeyDown={(event) => {
                        if (event.key === "Enter" || event.key === " ") {
                            event.preventDefault();
                            openTransaction(row.id);
                        }
                    }}
                    title="View Event Transaction"
                >
                    {row.eventId ?? row.id}
                </span>
            );
        }
        if (key === "documents")
            return Array.isArray(row.documents) && row.documents.length ? (
                <div className="fad-docs">
                    {row.documents.map((doc, i) => (
                        <a
                            key={i}
                            href={doc.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            title={doc.label || `Document ${i + 1}`}
                        >
                            <FileText size={14} />
                            {i + 1}
                        </a>
                    ))}
                </div>
            ) : (
                "—"
            );
        if (key === "event_status")
            return (
                <span
                    className={`fad-status ${String(row[key] || "")
                        .toLowerCase()
                        .replace(/[^a-z_]/g, "")}`}
                >
                    {fmt(row[key])}
                </span>
            );
        if (key === "event_submitted" || key === "updated")
            return date(row[key]);
        if (key === "comment")
            return (
                <span title={fmt(row[key])} className="fad-truncate">
                    {fmt(row[key])}
                </span>
            );
        return (
            <span title={fmt(row[key])} className="fad-truncate">
                {fmt(row[key])}
            </span>
        );
    };
    const summaryCards = [
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

    return (
        <section className="fad-page">
            <div className="fad-heading">
                <button type="button" className="fad-back-btn" onClick={onBack}>
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
                    onClick={() => setReload((x) => x + 1)}
                    disabled={loading}
                >
                    <RefreshCw size={16} /> Refresh
                </button>
            </div>
            <div className="fad-agent">
                {loading && !result ? (
                    <>
                        <Skeleton width="180px" />
                        <Skeleton width="260px" />
                    </>
                ) : (
                    <>
                        <div className="fad-avatar">
                            <Users size={20} />
                        </div>
                        <div>
                            <strong>
                                {result?.agent?.name ?? "Field Agent"}
                            </strong>
                            <small>
                                {result?.agent?.agentId ?? "—"} ·{" "}
                                {result?.agent?.mobile ?? "—"} ·{" "}
                                {result?.agent?.center ?? "—"}
                            </small>
                        </div>
                    </>
                )}
            </div>
            <div className="fad-stats">
                {summaryCards.map(
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

                                {loading && !result ? (
                                    <Skeleton width="55px" />
                                ) : (
                                    <strong className="fad-stat-value">
                                        {Number(
                                            result?.summary?.[key] ?? 0,
                                        ).toLocaleString("en-IN")}
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
            <div className="fad-panel">
                <div className="fad-toolbar">
                    <label className="fad-search">
                        <Search size={17} />
                        <input
                            value={search}
                            onChange={(e) => updateSearch(e.target.value)}
                            placeholder="Search Event ID, name, beneficiary, mobile..."
                        />
                    </label>
                    <select
                        value={status}
                        onChange={(e) => updateStatus(e.target.value)}
                    >
                        <option value="">All Statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="action_required">Action Required</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <div className="fad-columns">
                        <button
                            type="button"
                            onClick={() => setShowColumns((x) => !x)}
                        >
                            Columns ({visible.length}/{COLUMNS.length})
                        </button>
                        {showColumns && (
                            <div className="fad-column-menu">
                                <button
                                    type="button"
                                    onClick={() => setHidden([])}
                                >
                                    Show all
                                </button>
                                {COLUMNS.map(([key, label]) => (
                                    <label key={key}>
                                        <input
                                            type="checkbox"
                                            checked={!hidden.includes(key)}
                                            onChange={() =>
                                                setHidden((prev) =>
                                                    prev.includes(key)
                                                        ? prev.filter(
                                                              (x) => x !== key,
                                                          )
                                                        : [...prev, key],
                                                )
                                            }
                                        />
                                        {label}
                                    </label>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
                {error && (
                    <div className="fad-error">
                        <AlertCircle size={17} />
                        {error}
                        <button
                            type="button"
                            onClick={() => setReload((x) => x + 1)}
                        >
                            Retry
                        </button>
                    </div>
                )}
                <div className="fad-table-scroll">
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
                                    { length: Math.min(perPage, 8) },
                                    (_, i) => (
                                        <tr key={i}>
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
                            ) : records.length ? (
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
                <div className="fad-footer">
                    <span>
                        {loading
                            ? "Loading transactions..."
                            : `Showing ${start}–${end} of ${pagination?.total ?? 0} results`}
                    </span>
                    <div>
                        <label>
                            Rows per page{" "}
                            <select
                                value={perPage}
                                onChange={(e) => {
                                    setPerPage(Number(e.target.value));
                                    setPage(1);
                                }}
                            >
                                {[10, 25, 50, 100].map((n) => (
                                    <option key={n} value={n}>
                                        {n}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <button
                            disabled={loading || page <= 1}
                            onClick={() => setPage((p) => p - 1)}
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>
                        <span>
                            {pagination?.current_page ?? page} /{" "}
                            {pagination?.last_page ?? 1}
                        </span>
                        <button
                            disabled={
                                loading || page >= (pagination?.last_page ?? 1)
                            }
                            onClick={() => setPage((p) => p + 1)}
                            aria-label="Next page"
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </div>
            </div>
            {selectedTransactionId !== null && (
                <EventTransactionDrawer
                    transactionId={selectedTransactionId}
                    onClose={closeTransaction}
                />
            )}
        </section>
    );
}
