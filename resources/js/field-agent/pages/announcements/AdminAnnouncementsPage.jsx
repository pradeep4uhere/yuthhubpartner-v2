import React, { useCallback, useEffect, useState } from "react";
import {
    Megaphone,
    Search,
    RefreshCw,
    Eye,
    CheckCheck,
    Clock3,
    X,
    ChevronLeft,
    ChevronRight,
    AlertCircle,
    Paperclip,
    FileText,
    Image as ImageIcon,
    ExternalLink,
} from "lucide-react";

import "./AdminAnnouncements.css";

const API = "/api/field-agent/admin-announcements";

/**
 * Format an announcement date for the Field Agent dashboard.
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
 * Read the Laravel CSRF token from the current page.
 */
const getCsrfToken = () => {
    return (
        document.querySelector('meta[name="csrf-token"]')?.content ||
        document.querySelector('input[name="_token"]')?.value ||
        ""
    );
};

/**
 * Return the appropriate attachment icon for a filename.
 */
const AttachmentIcon = ({ filename }) => {
    const extension = String(filename || "")
        .split(".")
        .pop()
        .toLowerCase();

    if (["jpg", "jpeg", "png", "webp"].includes(extension)) {
        return <ImageIcon size={17} />;
    }

    return <FileText size={17} />;
};

/**
 * Display the priority of an Admin announcement.
 */
const PriorityBadge = ({ priority }) => {
    const value = String(priority || "normal").toLowerCase();

    return (
        <span className={`aa-field-priority aa-field-priority--${value}`}>
            {value}
        </span>
    );
};

export default function AdminAnnouncementsPage({ onUnreadChange }) {
    const [items, setItems] = useState([]);
    const [pagination, setPagination] = useState(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState("");

    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [priority, setPriority] = useState("all");
    const [page, setPage] = useState(1);

    const [selected, setSelected] = useState(null);
    const [detailLoading, setDetailLoading] = useState(false);
    const [marking, setMarking] = useState(false);

    /**
     * Fetch the Admin announcements assigned to the authenticated agent.
     */
    const loadAnnouncements = useCallback(
        async (showRefresh = false) => {
            try {
                if (showRefresh) {
                    setRefreshing(true);
                } else {
                    setLoading(true);
                }

                setError("");

                const params = new URLSearchParams({
                    page: String(page),
                    status,
                });

                if (search.trim()) {
                    params.set("search", search.trim());
                }

                if (priority !== "all") {
                    params.set("priority", priority);
                }

                const response = await fetch(`${API}?${params.toString()}`, {
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || "Unable to load Admin announcements.",
                    );
                }

                const paginator = result.announcements;

                setItems(
                    (paginator?.data || []).filter((item) => item.announcement),
                );

                setPagination(paginator || null);
            } catch (requestError) {
                console.error("Admin announcements error:", requestError);
                setError(
                    requestError.message ||
                        "Unable to load Admin announcements.",
                );
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [page, status, priority, search],
    );

    /**
     * Reload the announcement list when a filter or page changes.
     */
    useEffect(() => {
        loadAnnouncements();
    }, [loadAnnouncements]);

    /**
     * Open the details of an assigned Admin announcement.
     */
    const openDetails = async (item) => {
        const announcementId = item.announcement?.id;

        if (!announcementId) return;

        try {
            setDetailLoading(true);

            const response = await fetch(`${API}/${announcementId}`, {
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Unable to load announcement details.",
                );
            }

            setSelected({
                ...result,
                announcement: result.announcement,
            });

            if (!result.is_read) {
                await markAsRead(announcementId);
            }
        } catch (requestError) {
            console.error("Admin announcement detail error:", requestError);
            setError(
                requestError.message || "Unable to load announcement details.",
            );
        } finally {
            setDetailLoading(false);
        }
    };

    /**
     * Mark an assigned Admin announcement as read and refresh its unread count.
     */
    const markAsRead = async (announcementId) => {
        try {
            setMarking(true);

            const response = await fetch(`${API}/${announcementId}/read`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Unable to mark announcement as read.",
                );
            }

            setSelected((current) => {
                if (!current) return current;

                return {
                    ...current,
                    is_read: true,
                    read_at: result.read_at,
                };
            });

            setItems((current) =>
                current.map((item) => {
                    if (item.announcement?.id !== announcementId) {
                        return item;
                    }

                    return {
                        ...item,
                        read: {
                            read_at: result.read_at,
                        },
                    };
                }),
            );

            onUnreadChange?.();
        } catch (requestError) {
            console.error("Admin announcement read error:", requestError);
            setError(
                requestError.message ||
                    "Unable to update announcement read status.",
            );
        } finally {
            setMarking(false);
        }
    };

    /**
     * Reset pagination when the announcement filters change.
     */
    const updateFilter = (setter, value) => {
        setter(value);
        setPage(1);
    };

    /**
     * Close the announcement details drawer.
     */
    const closeDetails = () => {
        setSelected(null);
    };

    const selectedAnnouncement = selected?.announcement;

    return (
        <div className="aa-field-page">
            <div className="aa-field-heading">
                <div>
                    <span className="aa-field-eyebrow">
                        OFFICIAL COMMUNICATION
                    </span>
                    <h2>Admin Announcements</h2>
                    <p>
                        Important updates and notifications published by
                        YouthHub Administration.
                    </p>
                </div>

                <button
                    type="button"
                    className="aa-field-refresh"
                    onClick={() => loadAnnouncements(true)}
                    disabled={refreshing}
                    title="Refresh announcements"
                >
                    <RefreshCw
                        size={16}
                        className={refreshing ? "aa-field-spinning" : ""}
                    />
                    Refresh
                </button>
            </div>

            <div className="aa-field-toolbar">
                <div className="aa-field-search">
                    <Search size={17} />
                    <input
                        type="search"
                        placeholder="Search announcements..."
                        value={search}
                        onChange={(event) =>
                            updateFilter(setSearch, event.target.value)
                        }
                    />
                    {search && (
                        <button
                            type="button"
                            onClick={() => updateFilter(setSearch, "")}
                            aria-label="Clear search"
                        >
                            <X size={15} />
                        </button>
                    )}
                </div>

                <select
                    value={status}
                    onChange={(event) =>
                        updateFilter(setStatus, event.target.value)
                    }
                    aria-label="Filter by read status"
                >
                    <option value="all">All announcements</option>
                    <option value="unread">Unread</option>
                    <option value="read">Read</option>
                </select>

                <select
                    value={priority}
                    onChange={(event) =>
                        updateFilter(setPriority, event.target.value)
                    }
                    aria-label="Filter by priority"
                >
                    <option value="all">All priorities</option>
                    <option value="normal">Normal</option>
                    <option value="important">Important</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>

            {error && (
                <div className="aa-field-error">
                    <AlertCircle size={17} />
                    <span>{error}</span>
                    <button type="button" onClick={() => setError("")}>
                        <X size={15} />
                    </button>
                </div>
            )}

            {loading ? (
                <div className="aa-field-state">
                    <span className="aa-field-loader" />
                    <p>Loading Admin announcements...</p>
                </div>
            ) : items.length === 0 ? (
                <div className="aa-field-empty">
                    <span>
                        <Megaphone size={27} />
                    </span>
                    <h3>No announcements found</h3>
                    <p>
                        There are no Admin announcements matching your current
                        filters.
                    </p>
                </div>
            ) : (
                <div className="aa-field-list">
                    {items.map((item) => {
                        const announcement = item.announcement;
                        const isRead = Boolean(item.read);

                        return (
                            <article
                                className={`aa-field-item ${
                                    isRead ? "" : "aa-field-item--unread"
                                }`}
                                key={item.id}
                            >
                                <div className="aa-field-item__icon">
                                    <Megaphone size={20} />
                                </div>

                                <div className="aa-field-item__content">
                                    <div className="aa-field-item__top">
                                        <div className="aa-field-item__badges">
                                            <PriorityBadge
                                                priority={announcement.priority}
                                            />

                                            {!isRead && (
                                                <span className="aa-field-unread">
                                                    <span />
                                                    New
                                                </span>
                                            )}
                                        </div>

                                        <span className="aa-field-date">
                                            <Clock3 size={13} />
                                            {formatDate(
                                                announcement.published_at,
                                            )}
                                        </span>
                                    </div>

                                    <h3>{announcement.title}</h3>

                                    <p>
                                        {String(announcement.message || "")
                                            .replace(/<[^>]*>/g, " ")
                                            .replace(/\s+/g, " ")
                                            .trim()
                                            .slice(0, 190)}
                                        {String(announcement.message || "")
                                            .length > 190
                                            ? "..."
                                            : ""}
                                    </p>

                                    <div className="aa-field-item__bottom">
                                        {announcement.attachment_name && (
                                            <span className="aa-field-attachment-label">
                                                <Paperclip size={14} />
                                                Attachment available
                                            </span>
                                        )}

                                        <button
                                            type="button"
                                            onClick={() => openDetails(item)}
                                            disabled={detailLoading}
                                        >
                                            <Eye size={15} />
                                            View details
                                        </button>
                                    </div>
                                </div>
                            </article>
                        );
                    })}
                </div>
            )}

            {pagination && pagination.last_page > 1 && (
                <div className="aa-field-pagination">
                    <span>
                        Showing {pagination.from || 0}–{pagination.to || 0} of{" "}
                        {pagination.total || 0}
                    </span>

                    <div>
                        <button
                            type="button"
                            disabled={!pagination.prev_page_url}
                            onClick={() =>
                                setPage((current) => Math.max(1, current - 1))
                            }
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>

                        <strong>
                            {pagination.current_page} / {pagination.last_page}
                        </strong>

                        <button
                            type="button"
                            disabled={!pagination.next_page_url}
                            onClick={() =>
                                setPage((current) =>
                                    Math.min(pagination.last_page, current + 1),
                                )
                            }
                            aria-label="Next page"
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </div>
            )}

            {selectedAnnouncement && (
                <div
                    className="aa-field-overlay"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) {
                            closeDetails();
                        }
                    }}
                >
                    <section
                        className="aa-field-drawer"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="aa-field-detail-title"
                    >
                        <header className="aa-field-drawer__header">
                            <div>
                                <span>ADMIN UPDATE</span>
                                <h3>Announcement details</h3>
                            </div>

                            <button
                                type="button"
                                onClick={closeDetails}
                                aria-label="Close details"
                            >
                                <X size={20} />
                            </button>
                        </header>

                        <div className="aa-field-drawer__body">
                            <div className="aa-field-detail-badges">
                                <PriorityBadge
                                    priority={selectedAnnouncement.priority}
                                />

                                {selected.is_read && (
                                    <span className="aa-field-read-badge">
                                        <CheckCheck size={14} />
                                        Read
                                    </span>
                                )}
                            </div>

                            <h2 id="aa-field-detail-title">
                                {selectedAnnouncement.title}
                            </h2>

                            <span className="aa-field-detail-date">
                                <Clock3 size={14} />
                                Published{" "}
                                {formatDate(selectedAnnouncement.published_at)}
                            </span>

                            <div className="aa-field-message">
                                {selectedAnnouncement.message || ""}
                            </div>

                            {selectedAnnouncement.attachment_name && (
                                <div className="aa-field-detail-attachment">
                                    <div>
                                        <AttachmentIcon
                                            filename={
                                                selectedAnnouncement.attachment_name
                                            }
                                        />
                                        <span>
                                            {
                                                selectedAnnouncement.attachment_name
                                            }
                                        </span>
                                    </div>

                                    <a
                                        href={`${API}/${selectedAnnouncement.id}/attachment`}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Preview
                                        <ExternalLink size={14} />
                                    </a>
                                </div>
                            )}

                            {selected.is_read && selected.read_at && (
                                <p className="aa-field-read-time">
                                    Read on {formatDate(selected.read_at)}
                                </p>
                            )}
                        </div>

                        <footer className="aa-field-drawer__footer">
                            <button
                                type="button"
                                className="aa-field-close-button"
                                onClick={closeDetails}
                            >
                                Close
                            </button>

                            {!selected.is_read && (
                                <button
                                    type="button"
                                    className="aa-field-mark-button"
                                    onClick={() =>
                                        markAsRead(selectedAnnouncement.id)
                                    }
                                    disabled={marking}
                                >
                                    <CheckCheck size={16} />
                                    {marking ? "Updating..." : "Mark as read"}
                                </button>
                            )}
                        </footer>
                    </section>
                </div>
            )}
        </div>
    );
}
