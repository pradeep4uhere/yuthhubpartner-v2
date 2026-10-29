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
    Image as ImageIcon,
    FileText,
    ExternalLink,
} from "lucide-react";

import "./Announcements.css";

const API = "/api/field-agent/announcements";

// Identify the uploaded attachment type using its original filename.
function getAttachmentType(filename = "") {
    const extension = filename.split(".").pop()?.toLowerCase();

    if (["jpg", "jpeg", "png"].includes(extension)) {
        return "image";
    }

    if (extension === "pdf") {
        return "pdf";
    }

    return "other";
}

// Format an announcement date for the Field Agent dashboard.
export function formatAnnouncementDate(value) {
    if (!value) return "—";

    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? "—"
        : date.toLocaleString("en-IN", {
              day: "2-digit",
              month: "short",
              year: "numeric",
              hour: "2-digit",
              minute: "2-digit",
          });
}

// Return the CSRF token configured by Laravel.
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
}

// Display a badge using the announcement priority.
export function PriorityBadge({ priority = "normal" }) {
    return (
        <span className={`fa-ann-priority fa-ann-priority--${priority}`}>
            {priority.charAt(0).toUpperCase() + priority.slice(1)}
        </span>
    );
}

// Render the complete Field Agent Announcement Management page.
export default function AnnouncementsPage({ onUnreadChange }) {
    const [items, setItems] = useState([]);
    const [pagination, setPagination] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [search, setSearch] = useState("");
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [page, setPage] = useState(1);
    const [selected, setSelected] = useState(null);
    const [detailLoading, setDetailLoading] = useState(false);
    const [marking, setMarking] = useState(false);

    // Debounce the search to avoid sending a request on every keystroke.
    useEffect(() => {
        const timeout = setTimeout(
            () => setDebouncedSearch(search.trim()),
            350,
        );
        return () => clearTimeout(timeout);
    }, [search]);

    // Fetch announcements assigned to the authenticated Field Agent.
    const loadAnnouncements = useCallback(
        async (signal) => {
            setLoading(true);
            setError("");

            try {
                const params = new URLSearchParams({
                    page: String(page),
                    status,
                });

                if (debouncedSearch) params.set("search", debouncedSearch);

                const response = await fetch(`${API}?${params}`, {
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                    signal,
                });

                if (!response.ok)
                    throw new Error("Unable to load announcements.");

                const result = await response.json();
                setItems(result.announcements?.data || []);
                setPagination(result.announcements || {});
            } catch (err) {
                if (err.name !== "AbortError") setError(err.message);
            } finally {
                if (!signal?.aborted) setLoading(false);
            }
        },
        [page, status, debouncedSearch],
    );

    // Refresh the listing whenever its active filters change.
    useEffect(() => {
        const controller = new AbortController();
        loadAnnouncements(controller.signal);
        return () => controller.abort();
    }, [loadAnnouncements]);

    // Open the selected announcement after verifying access through the API.
    const openAnnouncement = async (item) => {
        setSelected({
            ...item,
            announcement: item.announcement,
        });
        setDetailLoading(true);
        setError("");

        try {
            const response = await fetch(`${API}/${item.announcement.id}`, {
                credentials: "same-origin",
                headers: { Accept: "application/json" },
            });

            if (!response.ok) throw new Error("Unable to open announcement.");

            const result = await response.json();

            setSelected({
                ...item,
                announcement: result.announcement,
                read: result.is_read ? { read_at: result.read_at } : null,
            });
        } catch (err) {
            setSelected(null);
            setError(err.message);
        } finally {
            setDetailLoading(false);
        }
    };

    // Mark an assigned announcement as read and synchronize the listing.
    const markAsRead = async () => {
        if (!selected?.announcement || selected.read || marking) return;

        setMarking(true);
        setError("");

        try {
            const response = await fetch(
                `${API}/${selected.announcement.id}/read`,
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                },
            );

            if (!response.ok)
                throw new Error("Unable to mark announcement as read.");

            const result = await response.json();
            const read = { read_at: result.read_at };

            setSelected((current) =>
                current ? { ...current, read } : current,
            );

            setItems((current) =>
                current.map((item) =>
                    item.id === selected.id ? { ...item, read } : item,
                ),
            );

            onUnreadChange?.();
            loadAnnouncements();
        } catch (err) {
            setError(err.message);
        } finally {
            setMarking(false);
        }
    };

    return (
        <div className="fa-ann-page">
            <header className="fa-ann-header">
                <div>
                    <span className="fa-ann-eyebrow">
                        PARTNER COMMUNICATION
                    </span>
                    <h2>My Announcements</h2>
                    <p>
                        Stay informed with important updates from your Partner.
                    </p>
                </div>

                <button
                    className="fa-ann-refresh"
                    onClick={() => loadAnnouncements()}
                    type="button"
                >
                    <RefreshCw size={16} /> Refresh
                </button>
            </header>

            <div className="fa-ann-toolbar">
                <div className="fa-ann-search">
                    <Search size={18} />
                    <input
                        value={search}
                        onChange={(e) => {
                            setSearch(e.target.value);
                            setPage(1);
                        }}
                        placeholder="Search announcements..."
                    />
                </div>

                <div className="fa-ann-tabs">
                    {["all", "unread", "read"].map((value) => (
                        <button
                            type="button"
                            key={value}
                            className={status === value ? "active" : ""}
                            onClick={() => {
                                setStatus(value);
                                setPage(1);
                            }}
                        >
                            {value.charAt(0).toUpperCase() + value.slice(1)}
                        </button>
                    ))}
                </div>
            </div>

            {error && (
                <div className="fa-ann-error">
                    <AlertCircle size={17} /> {error}
                </div>
            )}

            <div className="fa-ann-list">
                {loading ? (
                    <div className="fa-ann-empty">Loading announcements...</div>
                ) : items.length === 0 ? (
                    <div className="fa-ann-empty">
                        <Megaphone size={32} />
                        <strong>No announcements found</strong>
                        <span>
                            There are no announcements matching your filters.
                        </span>
                    </div>
                ) : (
                    items.map((item) => {
                        const announcement = item.announcement;
                        if (!announcement) return null;

                        return (
                            <article
                                key={item.id}
                                className={`fa-ann-card ${!item.read ? "is-unread" : ""}`}
                            >
                                <div className="fa-ann-card-icon">
                                    <Megaphone size={21} />
                                </div>

                                <div className="fa-ann-card-content">
                                    <div className="fa-ann-card-top">
                                        <h3>{announcement.title}</h3>
                                        <PriorityBadge
                                            priority={announcement.priority}
                                        />
                                    </div>

                                    <p>{announcement.message}</p>

                                    <div className="fa-ann-meta">
                                        <span>
                                            <Clock3 size={13} />
                                            {formatAnnouncementDate(
                                                announcement.published_at,
                                            )}
                                        </span>

                                        <span
                                            className={
                                                item.read
                                                    ? "fa-ann-read"
                                                    : "fa-ann-unread"
                                            }
                                        >
                                            {item.read ? "Read" : "Unread"}
                                        </span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    className="fa-ann-view"
                                    onClick={() => openAnnouncement(item)}
                                >
                                    <Eye size={16} /> View
                                </button>
                            </article>
                        );
                    })
                )}
            </div>

            <footer className="fa-ann-footer">
                <span>
                    Page {pagination.current_page || 1} of{" "}
                    {pagination.last_page || 1}
                    {" · "}
                    {pagination.total || 0} announcements
                </span>

                <div>
                    <button
                        disabled={loading || page <= 1}
                        onClick={() => setPage((current) => current - 1)}
                        aria-label="Previous page"
                    >
                        <ChevronLeft size={18} />
                    </button>
                    <button
                        disabled={
                            loading || page >= (pagination.last_page || 1)
                        }
                        onClick={() => setPage((current) => current + 1)}
                        aria-label="Next page"
                    >
                        <ChevronRight size={18} />
                    </button>
                </div>
            </footer>

            {selected && (
                <div
                    className="fa-ann-overlay"
                    onMouseDown={() => setSelected(null)}
                >
                    <aside
                        className="fa-ann-drawer"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Announcement details"
                        onMouseDown={(e) => e.stopPropagation()}
                    >
                        <div className="fa-ann-drawer-head">
                            <div>
                                <span className="fa-ann-eyebrow">
                                    ANNOUNCEMENT DETAILS
                                </span>
                                <h3>Partner Update</h3>
                            </div>

                            <button
                                type="button"
                                onClick={() => setSelected(null)}
                                aria-label="Close details"
                            >
                                <X size={21} />
                            </button>
                        </div>

                        {detailLoading ? (
                            <div className="fa-ann-empty">
                                Loading details...
                            </div>
                        ) : (
                            <>
                                <div className="fa-ann-drawer-body">
                                    <PriorityBadge
                                        priority={
                                            selected.announcement.priority
                                        }
                                    />

                                    <h2>{selected.announcement.title}</h2>

                                    <div className="fa-ann-meta">
                                        <Clock3 size={14} />
                                        {formatAnnouncementDate(
                                            selected.announcement.published_at,
                                        )}
                                    </div>

                                    <div className="fa-ann-message">
                                        {selected.announcement.message}
                                    </div>

                                    {/* Preview Partner-uploaded announcement attachments. */}
                                    {selected.announcement.attachment_name &&
                                        (() => {
                                            const announcement =
                                                selected.announcement;

                                            const attachmentType =
                                                getAttachmentType(
                                                    announcement.attachment_name,
                                                );

                                            const attachmentUrl = `${API}/${announcement.id}/attachment`;

                                            return (
                                                <div className="fa-ann-attachment-preview">
                                                    <div className="fa-ann-attachment-heading">
                                                        <span>
                                                            <ImageIcon
                                                                size={17}
                                                            />
                                                            Partner Attachment
                                                        </span>
                                                    </div>

                                                    {/* Display uploaded JPG, JPEG and PNG images. */}
                                                    {attachmentType ===
                                                        "image" && (
                                                        <a
                                                            href={attachmentUrl}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="fa-ann-image-link"
                                                            title="Open full image"
                                                        >
                                                            <img
                                                                src={
                                                                    attachmentUrl
                                                                }
                                                                alt={
                                                                    announcement.attachment_name
                                                                }
                                                                className="fa-ann-preview-image"
                                                                loading="lazy"
                                                            />

                                                            <span className="fa-ann-image-overlay">
                                                                <ExternalLink
                                                                    size={16}
                                                                />
                                                                View Full Image
                                                            </span>
                                                        </a>
                                                    )}

                                                    {/* Display a PDF preview with an option to open it. */}
                                                    {attachmentType ===
                                                        "pdf" && (
                                                        <div className="fa-ann-pdf-preview">
                                                            <FileText
                                                                size={26}
                                                            />

                                                            <div>
                                                                <strong>
                                                                    PDF Document
                                                                </strong>
                                                                <span>
                                                                    {
                                                                        announcement.attachment_name
                                                                    }
                                                                </span>
                                                            </div>

                                                            <a
                                                                href={
                                                                    attachmentUrl
                                                                }
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                            >
                                                                <ExternalLink
                                                                    size={15}
                                                                />
                                                                View PDF
                                                            </a>
                                                        </div>
                                                    )}

                                                    <div className="fa-ann-attachment-filename">
                                                        {
                                                            announcement.attachment_name
                                                        }
                                                    </div>
                                                </div>
                                            );
                                        })()}

                                    <span
                                        className={
                                            selected.read
                                                ? "fa-ann-read"
                                                : "fa-ann-unread"
                                        }
                                    >
                                        {selected.read ? "Read" : "Unread"}
                                    </span>
                                </div>

                                <div className="fa-ann-drawer-footer">
                                    <button
                                        type="button"
                                        className="fa-ann-secondary"
                                        onClick={() => setSelected(null)}
                                    >
                                        Close
                                    </button>

                                    {!selected.read && (
                                        <button
                                            type="button"
                                            className="fa-ann-primary"
                                            disabled={marking}
                                            onClick={markAsRead}
                                        >
                                            <CheckCheck size={17} />
                                            {marking
                                                ? "Saving..."
                                                : "Mark as Read"}
                                        </button>
                                    )}
                                </div>
                            </>
                        )}
                    </aside>
                </div>
            )}
        </div>
    );
}
