import { useEffect, useState } from "react";
import {
    X,
    UserRound,
    Phone,
    CalendarDays,
    ReceiptText,
    FileText,
    MessageSquareText,
    Clock3,
    CheckCircle2,
    AlertCircle,
    RefreshCw,
    IndianRupee,
    Hash,
    Building2,
    BriefcaseBusiness,
    ExternalLink,
    Layers3,
    Activity,
    Info,
} from "lucide-react";

import "./EventTransactionDrawer.css";

/* =========================================
   HELPERS
========================================= */

const formatDate = (value) => {
    if (!value) return "—";

    const raw = String(value);
    const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (!match) return "—";

    const [, year, month, day] = match;

    const date = new Date(
        Date.UTC(Number(year), Number(month) - 1, Number(day)),
    );

    const formatted = date.toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        timeZone: "UTC",
    });

    // Only show time when source includes an explicit timestamp.
    if (!raw.includes("T") && !raw.includes(" ")) {
        return formatted;
    }

    const parsed = new Date(raw);

    if (Number.isNaN(parsed.getTime())) return formatted;

    return parsed.toLocaleString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatAmount = (value) => {
    if (value === null || value === undefined || value === "") {
        return "—";
    }

    return Number(value).toLocaleString("en-IN", {
        style: "currency",
        currency: "INR",
    });
};

const formatLabel = (value) => {
    if (value === null || value === undefined || value === "") {
        return "—";
    }

    return String(value)
        .replace(/[_-]/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const initials = (name) =>
    String(name || "Beneficiary")
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0] || "")
        .join("")
        .toUpperCase();

/* =========================================
   SKELETON
========================================= */

function Skeleton({ width = "100%", height = 14, circle = false }) {
    return (
        <span
            className={`etd-skeleton ${circle ? "etd-skeleton-circle" : ""}`}
            style={{ width, height }}
            aria-hidden="true"
        />
    );
}

function DrawerSkeleton() {
    return (
        <div className="etd-skeleton-content">
            <div className="etd-skeleton-hero">
                <Skeleton width={56} height={56} circle />

                <div className="etd-skeleton-stack">
                    <Skeleton width="65%" height={18} />
                    <Skeleton width="40%" height={12} />
                </div>
            </div>

            <div className="etd-skeleton-grid">
                {Array.from({ length: 4 }, (_, index) => (
                    <div className="etd-skeleton-card" key={index}>
                        <Skeleton width="55%" height={11} />
                        <Skeleton width="85%" height={18} />
                    </div>
                ))}
            </div>

            {Array.from({ length: 3 }, (_, index) => (
                <div className="etd-skeleton-section" key={index}>
                    <Skeleton width={150} height={18} />

                    {Array.from({ length: 3 }, (_, row) => (
                        <div className="etd-skeleton-line" key={row}>
                            <Skeleton width="35%" height={12} />
                            <Skeleton width="45%" height={12} />
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
}

/* =========================================
   STATUS BADGE
========================================= */

function StatusBadge({ value, type = "workflow" }) {
    const normalized = String(value ?? "")
        .toLowerCase()
        .trim()
        .replace(/[\s-]+/g, "_");

    let variant = "neutral";

    if (["approved", "accepted", "completed", "success"].includes(normalized)) {
        variant = "success";
    } else if (
        [
            "pending",
            "submitted",
            "under_review",
            "processing",
            "in_progress",
        ].includes(normalized)
    ) {
        variant = "warning";
    } else if (["rejected", "failed", "cancelled"].includes(normalized)) {
        variant = "danger";
    } else if (normalized === "draft") {
        variant = "info";
    }

    return (
        <span className={`etd-badge etd-badge-${variant}`}>
            <span className="etd-badge-dot" />

            {normalized
                ? formatLabel(normalized)
                : type === "review"
                  ? "Not Reviewed"
                  : "Unknown"}
        </span>
    );
}

/* =========================================
   REUSABLE UI
========================================= */

function Section({ icon: Icon, title, children, action }) {
    return (
        <section className="etd-section">
            <div className="etd-section-heading">
                <h3>
                    <Icon size={17} />
                    {title}
                </h3>

                {action}
            </div>

            {children}
        </section>
    );
}

function DetailItem({ label, value, icon: Icon }) {
    return (
        <div className="etd-detail-item">
            <span className="etd-detail-label">
                {Icon && <Icon size={13} />}
                {label}
            </span>

            <span className="etd-detail-value">
                {value === null || value === undefined || value === ""
                    ? "—"
                    : value}
            </span>
        </div>
    );
}

function DetailGrid({ children }) {
    return <div className="etd-detail-grid">{children}</div>;
}

/* =========================================
   TIMELINE
========================================= */

function EventTimeline({ items = [] }) {
    if (!items.length) {
        return (
            <div className="etd-empty">
                <Clock3 size={25} />
                <strong>No timeline available</strong>
                <span>No activity information was found.</span>
            </div>
        );
    }

    const icons = {
        created: ReceiptText,
        submitted: CheckCircle2,
        current: Activity,
        review: CheckCircle2,
        comment: MessageSquareText,
        workflow: RefreshCw,
    };

    return (
        <div className="etd-timeline">
            {items.map((item, index) => {
                const Icon = icons[item.type] || Clock3;

                return (
                    <div
                        className={`etd-timeline-item ${
                            index === items.length - 1
                                ? "etd-timeline-last"
                                : ""
                        }`}
                        key={item.id ?? index}
                    >
                        <div className="etd-timeline-track">
                            <span
                                className={`etd-timeline-icon etd-timeline-${item.type}`}
                            >
                                <Icon size={14} />
                            </span>

                            {index !== items.length - 1 && (
                                <span className="etd-timeline-line" />
                            )}
                        </div>

                        <div className="etd-timeline-content">
                            <div className="etd-timeline-title-row">
                                <strong>{item.title}</strong>

                                {item.date && (
                                    <time>{formatDate(item.date)}</time>
                                )}
                            </div>

                            {item.description && <p>{item.description}</p>}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

/* =========================================
   COMMENTS
========================================= */

function Comments({ comments = [] }) {
    if (!comments.length) {
        return (
            <div className="etd-empty">
                <MessageSquareText size={25} />
                <strong>No comments yet</strong>
                <span>Reviewer comments will appear here when available.</span>
            </div>
        );
    }

    return (
        <div className="etd-comments">
            {comments.map((item, index) => (
                <article className="etd-comment" key={item.id ?? index}>
                    <div className="etd-comment-top">
                        <span className="etd-comment-avatar">
                            <MessageSquareText size={16} />
                        </span>

                        <div>
                            <strong>{item.author || "Reviewer Comment"}</strong>

                            {item.created_at && (
                                <small>{formatDate(item.created_at)}</small>
                            )}
                        </div>

                        {item.is_latest && (
                            <span className="etd-latest-tag">Latest</span>
                        )}
                    </div>

                    <p>{item.comment}</p>
                </article>
            ))}
        </div>
    );
}

/* =========================================
   DOCUMENTS
========================================= */

function Documents({ documents = [] }) {
    if (!documents.length) {
        return (
            <div className="etd-empty">
                <FileText size={25} />
                <strong>No documents available</strong>
                <span>No documents have been uploaded.</span>
            </div>
        );
    }

    return (
        <div className="etd-documents">
            {documents.map((doc, index) => {
                const url = doc.url;

                return (
                    <div className="etd-document" key={doc.key ?? index}>
                        <span className="etd-document-icon">
                            <FileText size={19} />
                        </span>

                        <div className="etd-document-info">
                            <strong>{doc.name || "Document"}</strong>

                            <small>
                                {url
                                    ? "Available to view"
                                    : "Uploaded document"}
                            </small>
                        </div>

                        {url ? (
                            <a
                                href={url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="etd-document-action"
                                aria-label={`Open ${doc.name}`}
                            >
                                <ExternalLink size={16} />
                            </a>
                        ) : (
                            <span
                                className="etd-document-unavailable"
                                title="Secure document URL is not configured"
                            >
                                <Info size={16} />
                            </span>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

/* =========================================
   MAIN DRAWER
========================================= */

export default function EventTransactionDrawer({ transactionId, onClose }) {
    const [transaction, setTransaction] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [activeTab, setActiveTab] = useState("overview");
    const [refreshKey, setRefreshKey] = useState(0);

    /*
    |--------------------------------------------------------------------------
    | Load Selected Transaction
    |--------------------------------------------------------------------------
    */

    useEffect(() => {
        if (!transactionId) return;

        const controller = new AbortController();

        async function loadDetails() {
            setLoading(true);
            setError("");
            setTransaction(null);

            try {
                const response = await fetch(
                    `/api/partner-placement/event-transactions/${transactionId}`,
                    {
                        credentials: "same-origin",
                        headers: {
                            Accept: "application/json",
                        },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error(
                        `Unable to load event details (HTTP ${response.status}).`,
                    );
                }

                const result = await response.json();

                if (result.success !== true) {
                    throw new Error(
                        result.message || "Unable to load event details.",
                    );
                }

                if (!controller.signal.aborted) {
                    setTransaction(result.data);
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

        loadDetails();

        return () => controller.abort();
    }, [transactionId, refreshKey]);

    /*
    |--------------------------------------------------------------------------
    | Escape Key + Background Scroll Lock
    |--------------------------------------------------------------------------
    */

    useEffect(() => {
        if (!transactionId) return;

        const previousOverflow = document.body.style.overflow;

        document.body.style.overflow = "hidden";

        const handleKeyDown = (event) => {
            if (event.key === "Escape") {
                onClose();
            }
        };

        document.addEventListener("keydown", handleKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [transactionId, onClose]);

    useEffect(() => {
        setActiveTab("overview");
    }, [transactionId]);

    if (!transactionId) return null;

    const tabs = [
        { id: "overview", label: "Overview", icon: Layers3 },
        { id: "documents", label: "Documents", icon: FileText },
        { id: "activity", label: "Activity", icon: Activity },
    ];

    const documents = transaction?.documents ?? [];
    const comments = transaction?.comments ?? [];
    const timeline = transaction?.timeline ?? [];

    return (
        <div className="etd-root">
            {/* BACKDROP */}

            <div
                className="etd-backdrop"
                onClick={onClose}
                aria-hidden="true"
            />

            {/* DRAWER */}

            <aside
                className="etd-drawer"
                role="dialog"
                aria-modal="true"
                aria-labelledby="etd-title"
            >
                {/* HEADER */}

                <header className="etd-header">
                    <div className="etd-header-info">
                        <span className="etd-eyebrow">
                            EVENT TRANSACTION DETAILS
                        </span>

                        <h2 id="etd-title">Event #{transactionId}</h2>

                        <p>Complete event information and activity</p>
                    </div>

                    <button
                        type="button"
                        className="etd-close"
                        onClick={onClose}
                        aria-label="Close event details"
                    >
                        <X size={21} />
                    </button>
                </header>

                {/* STATUS STRIP */}

                {!loading && transaction && (
                    <div className="etd-status-strip">
                        <div>
                            <span>Workflow Status</span>
                            <StatusBadge
                                value={transaction.workflow_status}
                                type="workflow"
                            />
                        </div>

                        <div>
                            <span>Review Status</span>
                            <StatusBadge
                                value={transaction.review_status}
                                type="review"
                            />
                        </div>
                    </div>
                )}

                {/* TABS */}

                {!loading && transaction && (
                    <nav
                        className="etd-tabs"
                        aria-label="Event detail sections"
                    >
                        {tabs.map((tab) => {
                            const Icon = tab.icon;

                            return (
                                <button
                                    type="button"
                                    key={tab.id}
                                    className={`etd-tab ${
                                        activeTab === tab.id
                                            ? "etd-tab-active"
                                            : ""
                                    }`}
                                    onClick={() => setActiveTab(tab.id)}
                                >
                                    <Icon size={16} />
                                    {tab.label}
                                </button>
                            );
                        })}
                    </nav>
                )}

                {/* SCROLLABLE CONTENT */}

                <div className="etd-body" tabIndex={-1}>
                    {loading ? (
                        <DrawerSkeleton />
                    ) : error ? (
                        <div className="etd-error">
                            <AlertCircle size={30} />

                            <strong>Unable to load event details</strong>

                            <p>{error}</p>

                            <button
                                type="button"
                                onClick={() =>
                                    setRefreshKey((value) => value + 1)
                                }
                            >
                                <RefreshCw size={15} />
                                Try Again
                            </button>
                        </div>
                    ) : transaction ? (
                        <>
                            {/* OVERVIEW TAB */}

                            {activeTab === "overview" && (
                                <div className="etd-tab-content">
                                    {/* BENEFICIARY HERO */}

                                    <div className="etd-beneficiary-hero">
                                        <div className="etd-avatar">
                                            {initials(
                                                transaction.beneficiary_name,
                                            )}
                                        </div>

                                        <div className="etd-beneficiary-main">
                                            <span>BENEFICIARY</span>

                                            <h3>
                                                {transaction.beneficiary_name ||
                                                    "Unnamed Beneficiary"}
                                            </h3>

                                            <p>
                                                <Phone size={13} />
                                                {transaction.beneficiary_phone_number ||
                                                    "Mobile not available"}
                                            </p>
                                        </div>
                                    </div>

                                    {/* HIGHLIGHT CARDS */}

                                    <div className="etd-highlight-grid">
                                        <div className="etd-highlight-card">
                                            <span className="etd-highlight-icon purple">
                                                <ReceiptText size={18} />
                                            </span>

                                            <small>Event Type</small>

                                            <strong>
                                                {transaction.event_type_name ||
                                                    "—"}
                                            </strong>
                                        </div>

                                        <div className="etd-highlight-card">
                                            <span className="etd-highlight-icon green">
                                                <IndianRupee size={18} />
                                            </span>

                                            <small>Event Value</small>

                                            <strong>
                                                {formatAmount(
                                                    transaction.event_value,
                                                )}
                                            </strong>
                                        </div>
                                    </div>

                                    {/* EVENT DETAILS */}

                                    <Section
                                        icon={ReceiptText}
                                        title="Event Information"
                                    >
                                        <DetailGrid>
                                            <DetailItem
                                                label="Transaction ID"
                                                value={`#${transaction.id}`}
                                                icon={Hash}
                                            />

                                            <DetailItem
                                                label="Event Type"
                                                value={
                                                    transaction.event_type_name
                                                }
                                            />

                                            <DetailItem
                                                label="Event Category"
                                                value={
                                                    transaction.event_category_name
                                                }
                                            />

                                            <DetailItem
                                                label="Event Value"
                                                value={formatAmount(
                                                    transaction.event_value,
                                                )}
                                            />

                                            <DetailItem
                                                label="Event Created Date"
                                                value={formatDate(
                                                    transaction.event_create_date,
                                                )}
                                                icon={CalendarDays}
                                            />

                                            <DetailItem
                                                label="Submitted Date"
                                                value={formatDate(
                                                    transaction.event_date_submitted,
                                                )}
                                                icon={CalendarDays}
                                            />
                                        </DetailGrid>
                                    </Section>
                                    {console.log(transaction)}
                                    {/* ASSOCIATED RECORDS */}

                                    <Section
                                        icon={Building2}
                                        title="Associated Records"
                                    >
                                        <DetailGrid>
                                            <DetailItem
                                                label="Learner ID"
                                                value={transaction.learner_id}
                                            />

                                            <DetailItem
                                                label="Partner"
                                                value={transaction.partner_name}
                                            />

                                            <DetailItem
                                                label="Partner Center"
                                                value={
                                                    transaction.partner_center_name
                                                }
                                            />

                                            <DetailItem
                                                label="Placement User ID"
                                                value={
                                                    transaction.partner_placement_user_id
                                                }
                                            />

                                            <DetailItem
                                                label="Field Agent"
                                                value={
                                                    transaction.field_agent_name +
                                                    " | " +
                                                    transaction.field_agent_sakhi_id
                                                }
                                            />

                                            <DetailItem
                                                label="Field Type"
                                                value={
                                                    transaction.field_type_name
                                                }
                                            />

                                            <DetailItem
                                                label="Industry Type"
                                                value={
                                                    transaction.industry_type_name
                                                }
                                            />
                                        </DetailGrid>
                                    </Section>

                                    {/* STATUS DETAILS */}

                                    <Section
                                        icon={Activity}
                                        title="Transaction Status"
                                    >
                                        <DetailGrid>
                                            <DetailItem
                                                label="Review Status"
                                                value={
                                                    <StatusBadge
                                                        value={
                                                            transaction.review_status
                                                        }
                                                        type="review"
                                                    />
                                                }
                                            />

                                            <DetailItem
                                                label="Workflow Status"
                                                value={
                                                    <StatusBadge
                                                        value={
                                                            transaction.workflow_status
                                                        }
                                                    />
                                                }
                                            />

                                            <DetailItem
                                                label="Record Status"
                                                value={
                                                    transaction.status
                                                        ? "Active"
                                                        : "Inactive"
                                                }
                                            />

                                            <DetailItem
                                                label="Last Updated"
                                                value={formatDate(
                                                    transaction.updated_at,
                                                )}
                                            />
                                        </DetailGrid>
                                    </Section>

                                    {/* LATEST COMMENT */}

                                    <Section
                                        icon={MessageSquareText}
                                        title="Latest Comment"
                                    >
                                        {transaction.last_comment ? (
                                            <div className="etd-latest-comment">
                                                <MessageSquareText size={17} />

                                                <p>
                                                    {transaction.last_comment}
                                                </p>
                                            </div>
                                        ) : (
                                            <p className="etd-muted">
                                                No reviewer comment available.
                                            </p>
                                        )}
                                    </Section>

                                    {/* SHORT TIMELINE */}

                                    <Section
                                        icon={Clock3}
                                        title="Event Timeline"
                                        action={
                                            <button
                                                type="button"
                                                className="etd-text-button"
                                                onClick={() =>
                                                    setActiveTab("activity")
                                                }
                                            >
                                                View Activity
                                            </button>
                                        }
                                    >
                                        <EventTimeline items={timeline} />
                                    </Section>
                                </div>
                            )}

                            {/* DOCUMENTS TAB */}

                            {activeTab === "documents" && (
                                <div className="etd-tab-content">
                                    <Section
                                        icon={FileText}
                                        title="Uploaded Documents"
                                    >
                                        <p className="etd-section-description">
                                            Supporting documents associated with
                                            this event transaction.
                                        </p>

                                        <Documents documents={documents} />
                                    </Section>

                                    <div className="etd-info-note">
                                        <Info size={17} />

                                        <span>
                                            Document access is subject to
                                            partner permissions. Secure document
                                            links must be configured in the
                                            backend.
                                        </span>
                                    </div>
                                </div>
                            )}

                            {/* ACTIVITY TAB */}

                            {activeTab === "activity" && (
                                <div className="etd-tab-content">
                                    <Section
                                        icon={Activity}
                                        title="Status Timeline"
                                    >
                                        <EventTimeline items={timeline} />
                                    </Section>

                                    <Section
                                        icon={MessageSquareText}
                                        title="Comments"
                                    >
                                        <Comments comments={comments} />
                                    </Section>

                                    <Section
                                        icon={Clock3}
                                        title="Record Timestamps"
                                    >
                                        <DetailGrid>
                                            <DetailItem
                                                label="Created At"
                                                value={formatDate(
                                                    transaction.created_at,
                                                )}
                                            />

                                            <DetailItem
                                                label="Last Updated"
                                                value={formatDate(
                                                    transaction.updated_at,
                                                )}
                                            />
                                        </DetailGrid>
                                    </Section>
                                </div>
                            )}
                        </>
                    ) : null}
                </div>

                {/* FIXED FOOTER */}

                <footer className="etd-footer">
                    <span>
                        <Info size={14} />
                        Read-only event details
                    </span>

                    <button
                        type="button"
                        className="etd-footer-close"
                        onClick={onClose}
                    >
                        Close Details
                    </button>
                </footer>
            </aside>
        </div>
    );
}
