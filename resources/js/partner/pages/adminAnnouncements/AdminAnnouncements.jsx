import { useCallback, useEffect, useState } from "react";
import {
    Bell,
    CalendarDays,
    Check,
    CheckCheck,
    ChevronLeft,
    ChevronRight,
    Clock3,
    FileText,
    Image as ImageIcon,
    LoaderCircle,
    Megaphone,
    RefreshCw,
    X,
} from "lucide-react";

import "./AdminAnnouncements.css";

const API = "/api/partner/admin-announcements";

/* Return a readable date and time for an announcement. */
function formatAnnouncementDate(value) {
    if (!value) return "Date unavailable";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "Date unavailable";

    return date.toLocaleString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

/* Return a CSS class for the announcement priority. */
function getPriorityClass(priority) {
    const value = String(priority || "normal").toLowerCase();

    if (value === "high" || value === "urgent") {
        return "paa-priority paa-priority--high";
    }

    if (value === "low") {
        return "paa-priority paa-priority--low";
    }

    return "paa-priority paa-priority--normal";
}

/* Read the Laravel CSRF token from the current page. */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
}

/* Admin announcements received by the authenticated Partner. */
export default function AdminAnnouncements({ onUnreadCountChange }) {
    const [announcements, setAnnouncements] = useState([]);
    const [selectedAnnouncement, setSelectedAnnouncement] = useState(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState("");
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [markingRead, setMarkingRead] = useState(false);

    /* Fetch the announcement list for the requested page. */
    const loadAnnouncements = useCallback(
        async (page = 1, showLoader = true) => {
            if (showLoader) setLoading(true);

            setError("");

            try {
                const response = await fetch(`${API}?page=${page}`, {
                    method: "GET",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (!response.ok) {
                    throw new Error("Unable to load Admin announcements.");
                }

                const data = await response.json();
                const items = Array.isArray(data.data) ? data.data : [];

                setAnnouncements(items);
                setCurrentPage(data.current_page || 1);
                setLastPage(data.last_page || 1);
                setTotal(data.total || 0);

                setSelectedAnnouncement((current) => {
                    if (!current) return null;

                    return items.find((item) => item.id === current.id) || null;
                });
            } catch (err) {
                console.error("Admin announcements error:", err);
                setError(err.message || "Something went wrong.");
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [],
    );

    /* Fetch the current unread count from the Partner API. */
    const loadUnreadCount = useCallback(async () => {
        try {
            const response = await fetch(`${API}/unread-count`, {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                },
            });

            if (!response.ok) return;

            const data = await response.json();

            onUnreadCountChange?.(Number(data.unread_count || 0));
        } catch (err) {
            console.error("Unread count error:", err);
        }
    }, [onUnreadCountChange]);

    /* Load announcements and unread count when the page opens. */
    useEffect(() => {
        loadAnnouncements(1);
        loadUnreadCount();
    }, [loadAnnouncements, loadUnreadCount]);

    /* Mark the selected announcement as read through the Partner API. */
    const markAsRead = async (announcement) => {
        if (!announcement || announcement.is_read || markingRead) return;

        setMarkingRead(true);

        try {
            const response = await fetch(`${API}/${announcement.id}/read`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(),
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                throw new Error("Unable to mark this announcement as read.");
            }

            const readAt = new Date().toISOString();

            const updatedAnnouncement = {
                ...announcement,
                is_read: true,
                read_at: readAt,
            };

            setAnnouncements((items) =>
                items.map((item) =>
                    item.id === announcement.id ? updatedAnnouncement : item,
                ),
            );

            setSelectedAnnouncement(updatedAnnouncement);

            await loadUnreadCount();
        } catch (err) {
            console.error("Mark announcement read error:", err);
            setError(err.message || "Unable to update read status.");
        } finally {
            setMarkingRead(false);
        }
    };

    /* Open an announcement and mark it as read when necessary. */
    const openAnnouncement = (announcement) => {
        setSelectedAnnouncement(announcement);

        if (!announcement.is_read) {
            markAsRead(announcement);
        }
    };

    /* Refresh the announcement list and unread count. */
    const refreshAnnouncements = async () => {
        setRefreshing(true);

        await Promise.all([
            loadAnnouncements(currentPage, false),
            loadUnreadCount(),
        ]);
    };

    /* Navigate to another page of announcements. */
    const changePage = (page) => {
        if (page < 1 || page > lastPage || page === currentPage) return;

        setSelectedAnnouncement(null);
        loadAnnouncements(page);
    };

    /* Render the attachment preview based on its file extension. */
    const renderAttachment = (announcement) => {
        if (!announcement?.has_attachment || !announcement.attachment_url) {
            return null;
        }

        const fileName = String(announcement.attachment_name || "");
        const extension = fileName.split(".").pop()?.toLowerCase();
        const isImage = ["png", "jpg", "jpeg"].includes(extension);
        const isPdf = extension === "pdf";

        if (isImage) {
            return (
                <div className="paa-attachment-preview">
                    <div className="paa-attachment-heading">
                        <ImageIcon size={17} />
                        <span>{fileName}</span>
                    </div>

                    <a
                        href={announcement.attachment_url}
                        target="_blank"
                        rel="noreferrer"
                        className="paa-image-link"
                    >
                        <img src={announcement.attachment_url} alt={fileName} />
                    </a>
                </div>
            );
        }

        if (isPdf) {
            return (
                <div className="paa-attachment-preview">
                    <div className="paa-attachment-heading">
                        <FileText size={17} />
                        <span>{fileName}</span>
                    </div>

                    <iframe
                        title={`Attachment: ${fileName}`}
                        src={announcement.attachment_url}
                        className="paa-pdf-preview"
                    />
                </div>
            );
        }

        return (
            <div className="paa-attachment-file">
                <FileText size={18} />
                <span>{fileName || "Attachment"}</span>
                <a
                    href={announcement.attachment_url}
                    target="_blank"
                    rel="noreferrer"
                >
                    Open
                </a>
            </div>
        );
    };

    return (
        <div className="paa-page">
            {/* PAGE HEADER */}

            <div className="paa-header">
                <div className="paa-header-title">
                    <div className="paa-header-icon">
                        <Megaphone size={23} />
                    </div>

                    <div>
                        <h1>Admin Announcements</h1>
                        <p>
                            Important updates and messages from YouthHub Admin.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    className="paa-refresh"
                    onClick={refreshAnnouncements}
                    disabled={refreshing || loading}
                >
                    <RefreshCw
                        size={15}
                        className={refreshing ? "paa-spin" : ""}
                    />
                    Refresh
                </button>
            </div>

            {/* SUMMARY */}

            <div className="paa-summary">
                <div className="paa-summary-icon">
                    <Bell size={20} />
                </div>

                <div>
                    <span>Total announcements</span>
                    <strong>{total}</strong>
                </div>

                <div className="paa-summary-note">
                    <span>
                        Messages assigned specifically to your Partner account
                    </span>
                </div>
            </div>

            {/* ERROR */}

            {error && (
                <div className="paa-error">
                    <span>{error}</span>

                    <button
                        type="button"
                        onClick={() => loadAnnouncements(currentPage)}
                    >
                        Retry
                    </button>
                </div>
            )}

            {/* ANNOUNCEMENT CONTENT */}

            <div className="paa-layout">
                <section className="paa-list-panel">
                    <div className="paa-section-heading">
                        <div>
                            <h2>Received Messages</h2>
                            <p>Announcements shared by Admin</p>
                        </div>

                        <span className="paa-total-pill">{total} Total</span>
                    </div>

                    {loading ? (
                        <div className="paa-loading">
                            <LoaderCircle size={27} className="paa-spin" />
                            <span>Loading announcements...</span>
                        </div>
                    ) : announcements.length === 0 ? (
                        <div className="paa-empty">
                            <div>
                                <Bell size={30} />
                            </div>
                            <strong>No announcements yet</strong>
                            <p>New messages from Admin will appear here.</p>
                        </div>
                    ) : (
                        <div className="paa-list">
                            {announcements.map((announcement) => (
                                <button
                                    type="button"
                                    key={announcement.id}
                                    className={`paa-list-item ${
                                        selectedAnnouncement?.id ===
                                        announcement.id
                                            ? "is-selected"
                                            : ""
                                    } ${
                                        !announcement.is_read ? "is-unread" : ""
                                    }`}
                                    onClick={() =>
                                        openAnnouncement(announcement)
                                    }
                                >
                                    <span className="paa-list-icon">
                                        <Megaphone size={18} />
                                    </span>

                                    <span className="paa-list-copy">
                                        <span className="paa-list-title">
                                            {announcement.title}
                                        </span>

                                        <span className="paa-list-message">
                                            {announcement.message}
                                        </span>

                                        <span className="paa-list-date">
                                            <CalendarDays size={12} />
                                            {formatAnnouncementDate(
                                                announcement.published_at,
                                            )}
                                        </span>
                                    </span>

                                    <span className="paa-list-status">
                                        {!announcement.is_read && (
                                            <span className="paa-unread-dot" />
                                        )}

                                        {announcement.is_read ? (
                                            <CheckCheck size={16} />
                                        ) : (
                                            <span>New</span>
                                        )}
                                    </span>
                                </button>
                            ))}
                        </div>
                    )}

                    {/* PAGINATION */}

                    {!loading && lastPage > 1 && (
                        <div className="paa-pagination">
                            <button
                                type="button"
                                onClick={() => changePage(currentPage - 1)}
                                disabled={currentPage <= 1}
                            >
                                <ChevronLeft size={16} />
                                Previous
                            </button>

                            <span>
                                Page {currentPage} of {lastPage}
                            </span>

                            <button
                                type="button"
                                onClick={() => changePage(currentPage + 1)}
                                disabled={currentPage >= lastPage}
                            >
                                Next
                                <ChevronRight size={16} />
                            </button>
                        </div>
                    )}
                </section>

                {/* ANNOUNCEMENT DETAILS */}

                <section className="paa-detail-panel">
                    {!selectedAnnouncement ? (
                        <div className="paa-detail-empty">
                            <div>
                                <Megaphone size={32} />
                            </div>

                            <strong>Select an announcement</strong>

                            <p>
                                Choose a message from the list to view its
                                complete details and attachments.
                            </p>
                        </div>
                    ) : (
                        <article className="paa-detail">
                            <div className="paa-detail-top">
                                <span className="paa-detail-label">
                                    <Bell size={14} />
                                    Admin Message
                                </span>

                                <span
                                    className={getPriorityClass(
                                        selectedAnnouncement.priority,
                                    )}
                                >
                                    {selectedAnnouncement.priority || "Normal"}
                                </span>
                            </div>

                            <h2>{selectedAnnouncement.title}</h2>

                            <div className="paa-detail-meta">
                                <span>
                                    <CalendarDays size={14} />
                                    {formatAnnouncementDate(
                                        selectedAnnouncement.published_at,
                                    )}
                                </span>

                                <span>
                                    <Clock3 size={14} />
                                    {selectedAnnouncement.expires_at
                                        ? `Expires ${formatAnnouncementDate(
                                              selectedAnnouncement.expires_at,
                                          )}`
                                        : "No expiry"}
                                </span>
                            </div>

                            <div className="paa-read-status">
                                {selectedAnnouncement.is_read ? (
                                    <>
                                        <CheckCheck size={15} />
                                        Read
                                        {selectedAnnouncement.read_at && (
                                            <small>
                                                on{" "}
                                                {formatAnnouncementDate(
                                                    selectedAnnouncement.read_at,
                                                )}
                                            </small>
                                        )}
                                    </>
                                ) : (
                                    <>
                                        <span className="paa-unread-dot" />
                                        Unread
                                    </>
                                )}
                            </div>

                            <div className="paa-detail-divider" />

                            <div className="paa-message">
                                {selectedAnnouncement.message}
                            </div>

                            {renderAttachment(selectedAnnouncement)}

                            {!selectedAnnouncement.is_read && (
                                <button
                                    type="button"
                                    className="paa-mark-read"
                                    onClick={() =>
                                        markAsRead(selectedAnnouncement)
                                    }
                                    disabled={markingRead}
                                >
                                    {markingRead ? (
                                        <LoaderCircle
                                            size={16}
                                            className="paa-spin"
                                        />
                                    ) : (
                                        <Check size={16} />
                                    )}
                                    Mark as read
                                </button>
                            )}
                        </article>
                    )}
                </section>
            </div>
        </div>
    );
}
