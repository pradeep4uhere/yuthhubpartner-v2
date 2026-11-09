import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
    Activity,
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    CircleHelp,
    Clock3,
    Download,
    Filter,
    LoaderCircle,
    MessageSquare,
    Paperclip,
    Plus,
    RefreshCw,
    Search,
    Send,
    ShieldCheck,
    Ticket,
    UserRound,
    X,
} from "lucide-react";

import "./AdminSupport.css";

/**
 * Admin Support & Tickets dashboard.
 * Uses the existing Admin Support APIs without changing requester modules.
 */
export default function AdminSupport({
    supportSummaryUrl,
    supportTicketsUrl,
    supportCategoriesUrl,
    supportAssigneesUrl,
    csrfToken,
}) {
    const [summary, setSummary] = useState({});
    const [tickets, setTickets] = useState([]);
    const [categories, setCategories] = useState([]);
    const [assignees, setAssignees] = useState([]);

    const [selectedTicket, setSelectedTicket] = useState(null);
    const [loading, setLoading] = useState(true);
    const [detailLoading, setDetailLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState("");

    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [priority, setPriority] = useState("all");
    const [categoryId, setCategoryId] = useState("all");
    const [assignedTo, setAssignedTo] = useState("all");

    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    const [replyMessage, setReplyMessage] = useState("");
    const [replyFiles, setReplyFiles] = useState([]);
    const [noteMessage, setNoteMessage] = useState("");
    const [activeComposer, setActiveComposer] = useState("reply");

    const [showCategoryForm, setShowCategoryForm] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [categoryForm, setCategoryForm] = useState({
        name: "",
        description: "",
        is_active: true,
        sort_order: 1,
    });

    /**
     * Sends authenticated requests to the Admin Support API using the current browser origin.
     */
    const apiRequest = useCallback(
        async (url, options = {}) => {
            const parsedUrl = new URL(url, window.location.origin);

            // Keep API requests on the exact same host and port as the Admin dashboard.
            const requestUrl =
                window.location.origin + parsedUrl.pathname + parsedUrl.search;

            const headers = {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...(options.headers || {}),
            };

            if (csrfToken) {
                headers["X-CSRF-TOKEN"] = csrfToken;
            }

            const response = await fetch(requestUrl, {
                ...options,
                credentials: "same-origin",
                headers,
            });

            const contentType = response.headers.get("content-type") || "";

            const result = contentType.includes("application/json")
                ? await response.json()
                : {};

            if (!response.ok) {
                throw new Error(
                    result.message || `Request failed (${response.status})`,
                );
            }

            return result;
        },
        [csrfToken],
    );

    /**
     * Loads summary statistics from the Admin Support API.
     */
    const loadSummary = useCallback(async () => {
        const result = await apiRequest(supportSummaryUrl);
        setSummary(result.data || {});
    }, [apiRequest, supportSummaryUrl]);

    /**
     * Loads tickets using the current filters and pagination.
     */
    const loadTickets = useCallback(
        async (page = 1) => {
            const params = new URLSearchParams({
                page: String(page),
                per_page: String(pagination.per_page),
            });

            if (search.trim()) params.set("search", search.trim());
            if (status !== "all") params.set("status", status);
            if (priority !== "all") params.set("priority", priority);
            if (categoryId !== "all") params.set("category_id", categoryId);

            if (assignedTo === "unassigned") {
                params.set("assigned_to", "");
            } else if (assignedTo !== "all") {
                params.set("assigned_to", assignedTo);
            }

            const result = await apiRequest(
                `${supportTicketsUrl}?${params.toString()}`,
            );

            setTickets(result.data || []);
            setPagination(
                result.meta || {
                    current_page: page,
                    last_page: 1,
                    per_page: 15,
                    total: 0,
                },
            );
        },
        [
            apiRequest,
            supportTicketsUrl,
            pagination.per_page,
            search,
            status,
            priority,
            categoryId,
            assignedTo,
        ],
    );

    /**
     * Loads categories and available Admin assignees.
     */
    const loadOptions = useCallback(async () => {
        const [categoryResult, assigneeResult] = await Promise.all([
            apiRequest(supportCategoriesUrl),
            apiRequest(supportAssigneesUrl),
        ]);

        setCategories(categoryResult.data || []);
        setAssignees(assigneeResult.data || []);
    }, [apiRequest, supportCategoriesUrl, supportAssigneesUrl]);

    /**
     * Loads the dashboard data and reports any API errors.
     */
    const loadDashboard = useCallback(async () => {
        setLoading(true);
        setError("");

        try {
            await Promise.all([loadSummary(), loadTickets(1), loadOptions()]);
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setLoading(false);
        }
    }, [loadSummary, loadTickets, loadOptions]);

    /**
     * Loads ticket details and opens the complete ticket conversation.
     */
    const openTicket = useCallback(
        async (ticketId) => {
            setDetailLoading(true);
            setError("");

            try {
                const result = await apiRequest(
                    `${supportTicketsUrl}/${ticketId}`,
                );

                if (!result.data || !result.data.id) {
                    throw new Error(
                        "Invalid ticket details received from server.",
                    );
                }

                setSelectedTicket(result.data);
                setReplyMessage("");
                setReplyFiles([]);
                setNoteMessage("");
                setActiveComposer("reply");
            } catch (requestError) {
                console.error(
                    "Admin Support ticket loading failed:",
                    requestError,
                );
                setError(requestError.message);
            } finally {
                setDetailLoading(false);
            }
        },
        [apiRequest, supportTicketsUrl],
    );

    /**
     * Refreshes the current list and summary after a ticket update.
     */
    const refreshDashboard = useCallback(async () => {
        try {
            await Promise.all([
                loadSummary(),
                loadTickets(pagination.current_page || 1),
            ]);

            if (selectedTicket?.id) {
                await openTicket(selectedTicket.id);
            }
        } catch (requestError) {
            setError(requestError.message);
        }
    }, [
        loadSummary,
        loadTickets,
        pagination.current_page,
        selectedTicket?.id,
        openTicket,
    ]);

    /**
     * Applies filters and reloads the first ticket page.
     */
    const applyFilters = useCallback(async () => {
        setLoading(true);
        setError("");

        try {
            await loadTickets(1);
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setLoading(false);
        }
    }, [loadTickets]);

    /**
     * Updates the selected ticket's status.
     */
    const updateTicketStatus = useCallback(
        async (nextStatus) => {
            if (!selectedTicket) return;

            setSaving(true);
            setError("");

            try {
                await apiRequest(
                    `${supportTicketsUrl}/${selectedTicket.id}/status`,
                    {
                        method: "PATCH",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ status: nextStatus }),
                    },
                );

                await refreshDashboard();
            } catch (requestError) {
                setError(requestError.message);
            } finally {
                setSaving(false);
            }
        },
        [apiRequest, selectedTicket, supportTicketsUrl, refreshDashboard],
    );

    /**
     * Updates the selected ticket's priority.
     */
    const updateTicketPriority = useCallback(
        async (nextPriority) => {
            if (!selectedTicket) return;

            setSaving(true);
            setError("");

            try {
                await apiRequest(
                    `${supportTicketsUrl}/${selectedTicket.id}/priority`,
                    {
                        method: "PATCH",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ priority: nextPriority }),
                    },
                );

                await refreshDashboard();
            } catch (requestError) {
                setError(requestError.message);
            } finally {
                setSaving(false);
            }
        },
        [apiRequest, selectedTicket, supportTicketsUrl, refreshDashboard],
    );

    /**
     * Assigns the selected ticket to an Admin or removes its assignment.
     */
    const updateTicketAssignee = useCallback(
        async (adminId) => {
            if (!selectedTicket) return;

            setSaving(true);
            setError("");

            try {
                await apiRequest(
                    `${supportTicketsUrl}/${selectedTicket.id}/assign`,
                    {
                        method: "PATCH",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            admin_id: adminId === "" ? null : Number(adminId),
                        }),
                    },
                );

                await refreshDashboard();
            } catch (requestError) {
                setError(requestError.message);
            } finally {
                setSaving(false);
            }
        },
        [apiRequest, selectedTicket, supportTicketsUrl, refreshDashboard],
    );

    /**
     * Sends a public reply with any selected attachments.
     */
    const sendReply = useCallback(
        async (event) => {
            event.preventDefault();

            if (!selectedTicket || !replyMessage.trim()) return;

            const formData = new FormData();
            formData.append("message", replyMessage.trim());

            replyFiles.forEach((file) => {
                formData.append("attachments[]", file);
            });

            setSaving(true);
            setError("");

            try {
                await apiRequest(
                    `${supportTicketsUrl}/${selectedTicket.id}/reply`,
                    {
                        method: "POST",
                        body: formData,
                    },
                );

                setReplyMessage("");
                setReplyFiles([]);
                await refreshDashboard();
            } catch (requestError) {
                setError(requestError.message);
            } finally {
                setSaving(false);
            }
        },
        [
            apiRequest,
            selectedTicket,
            replyMessage,
            replyFiles,
            supportTicketsUrl,
            refreshDashboard,
        ],
    );

    /**
     * Saves a private internal note on the selected ticket.
     */
    const sendInternalNote = useCallback(
        async (event) => {
            event.preventDefault();

            if (!selectedTicket || !noteMessage.trim()) return;

            setSaving(true);
            setError("");

            try {
                await apiRequest(
                    `${supportTicketsUrl}/${selectedTicket.id}/internal-note`,
                    {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ message: noteMessage.trim() }),
                    },
                );

                setNoteMessage("");
                await refreshDashboard();
            } catch (requestError) {
                setError(requestError.message);
            } finally {
                setSaving(false);
            }
        },
        [
            apiRequest,
            selectedTicket,
            noteMessage,
            supportTicketsUrl,
            refreshDashboard,
        ],
    );

    /**
     * Opens the category form for creating a new category.
     */
    const startNewCategory = useCallback(() => {
        setEditingCategory(null);
        setCategoryForm({
            name: "",
            description: "",
            is_active: true,
            sort_order: categories.length + 1,
        });
        setShowCategoryForm(true);
    }, [categories.length]);

    /**
     * Opens the category form with an existing category's details.
     */
    const startEditCategory = useCallback((category) => {
        setEditingCategory(category);
        setCategoryForm({
            name: category.name || "",
            description: category.description || "",
            is_active: Boolean(category.is_active),
            sort_order: category.sort_order || 1,
        });
        setShowCategoryForm(true);
    }, []);

    /**
     * Creates a category or updates the currently selected category.
     */
    const saveCategory = useCallback(
        async (event) => {
            event.preventDefault();

            setSaving(true);
            setError("");

            try {
                const isEditing = Boolean(editingCategory);
                const url = isEditing
                    ? `${supportCategoriesUrl}/${editingCategory.id}`
                    : supportCategoriesUrl;

                await apiRequest(url, {
                    method: isEditing ? "PATCH" : "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(categoryForm),
                });

                const result = await apiRequest(supportCategoriesUrl);
                setCategories(result.data || []);
                setShowCategoryForm(false);
                setEditingCategory(null);
            } catch (requestError) {
                setError(requestError.message);
            } finally {
                setSaving(false);
            }
        },
        [apiRequest, editingCategory, supportCategoriesUrl, categoryForm],
    );

    /**
     * Formats API timestamps for readable dashboard display.
     */
    const formatDate = useCallback((value) => {
        if (!value) return "—";

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) return value;

        return date.toLocaleString("en-IN", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    }, []);

    /**
     * Returns a readable label for a polymorphic requester type.
     */
    const requesterTypeLabel = useCallback((type) => {
        const labels = {
            PartnerPlacementUser: "Placement Partner",
            Partner: "Partner",
            FieldAgent: "Field Agent",
            PartnerCenter: "Partner Center",
        };

        return labels[type] || type || "Requester";
    }, []);

    /**
     * Loads initial dashboard data when the component is mounted.
     */
    useEffect(() => {
        loadDashboard();
    }, [loadDashboard]);

    /**
     * Applies a CSS status class for ticket status labels.
     */
    const statusClass = (value) => `ats-status ats-status-${value || "open"}`;

    /**
     * Applies a CSS priority class for ticket priority labels.
     */
    const priorityClass = (value) =>
        `ats-priority ats-priority-${value || "normal"}`;

    /**
     * Renders one summary statistic card.
     */
    const renderStat = (label, value, icon, tone) => {
        const Icon = icon;

        return (
            <div className={`ats-stat-card ats-stat-${tone}`} key={label}>
                <div className="ats-stat-icon">
                    <Icon size={19} />
                </div>
                <div className="ats-stat-copy">
                    <span>{label}</span>
                    <strong>{value ?? 0}</strong>
                </div>
            </div>
        );
    };

    /**
     * Handles a change to the ticket search field.
     */
    const handleSearchChange = (event) => setSearch(event.target.value);

    /**
     * Handles a change to the status filter.
     */
    const handleStatusChange = (event) => setStatus(event.target.value);

    /**
     * Handles a change to the priority filter.
     */
    const handlePriorityChange = (event) => setPriority(event.target.value);

    /**
     * Handles a change to the category filter.
     */
    const handleCategoryChange = (event) => setCategoryId(event.target.value);

    /**
     * Handles a change to the assignee filter.
     */
    const handleAssigneeChange = (event) => setAssignedTo(event.target.value);

    /**
     * Handles the public reply message field.
     */
    const handleReplyChange = (event) => setReplyMessage(event.target.value);

    /**
     * Handles the internal note message field.
     */
    const handleNoteChange = (event) => setNoteMessage(event.target.value);

    /**
     * Handles category form field updates.
     */
    const handleCategoryFieldChange = (event) => {
        const { name, value, type, checked } = event.target;

        setCategoryForm((current) => ({
            ...current,
            [name]:
                type === "checkbox"
                    ? checked
                    : name === "sort_order"
                      ? Number(value)
                      : value,
        }));
    };

    /**
     * Handles attachment selection for a public reply.
     */
    const handleReplyFilesChange = (event) => {
        setReplyFiles(Array.from(event.target.files || []).slice(0, 5));
    };

    /**
     * Moves the ticket list to the requested page.
     */
    const handlePageChange = async (page) => {
        if (page < 1 || page > pagination.last_page) return;

        setLoading(true);

        try {
            await loadTickets(page);
        } catch (requestError) {
            setError(requestError.message);
        } finally {
            setLoading(false);
        }
    };

    /**
     * Returns the attachment download URL for a ticket attachment.
     */
    const attachmentUrl = (attachment) => {
        const base = supportTicketsUrl.replace(/\/tickets\/?$/, "");
        return `${base}/attachments/${attachment.id}/download`;
    };

    /**
     * Renders a downloadable attachment link.
     */
    const renderAttachment = (attachment) => (
        <a
            className="ats-attachment"
            href={attachmentUrl(attachment)}
            target="_blank"
            rel="noreferrer"
            key={attachment.id}
        >
            <Paperclip size={14} />
            <span>
                {attachment.original_name || attachment.name || "Attachment"}
            </span>
            <Download size={14} />
        </a>
    );

    /**
     * Renders the ticket details and reply conversation.
     */
    const renderTicketDetails = () => {
        if (detailLoading) {
            return (
                <div className="ats-detail-loading">
                    <LoaderCircle className="ats-spin" size={28} />
                    Loading ticket details...
                </div>
            );
        }

        if (!selectedTicket) return null;

        const ticket = selectedTicket;
        const replies = ticket.replies || [];

        return (
            <section className="ats-detail-page">
                <div className="ats-detail-topbar">
                    <button
                        type="button"
                        className="ats-back-button"
                        onClick={() => setSelectedTicket(null)}
                    >
                        <ArrowLeft size={17} />
                        Back to tickets
                    </button>

                    <span className="ats-ticket-reference">
                        {ticket.ticket_number}
                    </span>
                </div>

                <div className="ats-detail-layout">
                    <div className="ats-detail-main">
                        <div className="ats-panel ats-ticket-heading">
                            <div className="ats-ticket-heading-top">
                                <div>
                                    <span className="ats-eyebrow">
                                        SUPPORT REQUEST
                                    </span>
                                    <h2>{ticket.subject}</h2>
                                    <p>
                                        Created {formatDate(ticket.created_at)}
                                    </p>
                                </div>

                                <span className={statusClass(ticket.status)}>
                                    {ticket.status}
                                </span>
                            </div>

                            <div className="ats-description-block">
                                <h4>Issue Description</h4>
                                <p>{ticket.description}</p>
                                {ticket.affected_module && (
                                    <span className="ats-module-tag">
                                        Module: {ticket.affected_module}
                                    </span>
                                )}
                            </div>

                            {ticket.attachments?.length > 0 && (
                                <div className="ats-attachments-block">
                                    <h4>Ticket Attachments</h4>
                                    <div className="ats-attachment-list">
                                        {ticket.attachments.map(
                                            renderAttachment,
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="ats-panel ats-conversation">
                            <div className="ats-panel-heading">
                                <div>
                                    <h3>Conversation</h3>
                                    <p>Replies and internal notes</p>
                                </div>
                                <MessageSquare size={19} />
                            </div>

                            {replies.length === 0 ? (
                                <div className="ats-empty-conversation">
                                    <MessageSquare size={25} />
                                    <strong>No replies yet</strong>
                                    <span>
                                        Start the conversation by sending a
                                        reply.
                                    </span>
                                </div>
                            ) : (
                                <div className="ats-timeline">
                                    {replies.map((reply) => (
                                        <article
                                            className={`ats-message ${
                                                reply.is_internal_note
                                                    ? "ats-message-internal"
                                                    : ""
                                            }`}
                                            key={reply.id}
                                        >
                                            <div className="ats-message-avatar">
                                                <UserRound size={17} />
                                            </div>

                                            <div className="ats-message-content">
                                                <div className="ats-message-meta">
                                                    <strong>
                                                        {reply.sender?.name ||
                                                            "Support team"}
                                                    </strong>
                                                    {reply.is_internal_note && (
                                                        <span className="ats-internal-label">
                                                            <ShieldCheck
                                                                size={12}
                                                            />
                                                            Internal note
                                                        </span>
                                                    )}
                                                    <time>
                                                        {formatDate(
                                                            reply.created_at,
                                                        )}
                                                    </time>
                                                </div>

                                                <p>{reply.message}</p>

                                                {reply.attachments?.length >
                                                    0 && (
                                                    <div className="ats-attachment-list">
                                                        {reply.attachments.map(
                                                            renderAttachment,
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="ats-panel ats-reply-panel">
                            <div className="ats-composer-tabs">
                                <button
                                    type="button"
                                    className={
                                        activeComposer === "reply"
                                            ? "active"
                                            : ""
                                    }
                                    onClick={() => setActiveComposer("reply")}
                                >
                                    <Send size={15} />
                                    Public Reply
                                </button>

                                <button
                                    type="button"
                                    className={
                                        activeComposer === "note"
                                            ? "active"
                                            : ""
                                    }
                                    onClick={() => setActiveComposer("note")}
                                >
                                    <ShieldCheck size={15} />
                                    Internal Note
                                </button>
                            </div>

                            {activeComposer === "reply" ? (
                                <form onSubmit={sendReply}>
                                    <textarea
                                        value={replyMessage}
                                        onChange={handleReplyChange}
                                        placeholder="Write a reply to the requester..."
                                        rows={4}
                                        required
                                    />

                                    <div className="ats-composer-footer">
                                        <label className="ats-file-button">
                                            <Paperclip size={15} />
                                            Attach files
                                            <input
                                                type="file"
                                                multiple
                                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.csv"
                                                onChange={
                                                    handleReplyFilesChange
                                                }
                                            />
                                        </label>

                                        <span className="ats-file-count">
                                            {replyFiles.length}/5 files selected
                                        </span>

                                        <button
                                            type="submit"
                                            className="ats-primary-button"
                                            disabled={
                                                saving || !replyMessage.trim()
                                            }
                                        >
                                            {saving ? (
                                                <LoaderCircle
                                                    className="ats-spin"
                                                    size={15}
                                                />
                                            ) : (
                                                <Send size={15} />
                                            )}
                                            Send Reply
                                        </button>
                                    </div>
                                </form>
                            ) : (
                                <form onSubmit={sendInternalNote}>
                                    <textarea
                                        value={noteMessage}
                                        onChange={handleNoteChange}
                                        placeholder="Write a private note for Admin team members..."
                                        rows={4}
                                        required
                                    />

                                    <div className="ats-composer-footer">
                                        <span className="ats-private-hint">
                                            <ShieldCheck size={14} />
                                            Only Admin users can see this note.
                                        </span>

                                        <button
                                            type="submit"
                                            className="ats-primary-button ats-note-button"
                                            disabled={
                                                saving || !noteMessage.trim()
                                            }
                                        >
                                            {saving ? (
                                                <LoaderCircle
                                                    className="ats-spin"
                                                    size={15}
                                                />
                                            ) : (
                                                <ShieldCheck size={15} />
                                            )}
                                            Add Internal Note
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </div>

                    <aside className="ats-detail-sidebar">
                        <div className="ats-panel">
                            <div className="ats-panel-heading">
                                <h3>Ticket Properties</h3>
                                <Activity size={18} />
                            </div>

                            <label className="ats-field-label">Status</label>
                            <select
                                value={ticket.status || "open"}
                                onChange={(event) =>
                                    updateTicketStatus(event.target.value)
                                }
                                disabled={saving}
                            >
                                <option value="open">Open</option>
                                <option value="pending">Pending</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>

                            <label className="ats-field-label">Priority</label>
                            <select
                                value={ticket.priority || "normal"}
                                onChange={(event) =>
                                    updateTicketPriority(event.target.value)
                                }
                                disabled={saving}
                            >
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>

                            <label className="ats-field-label">
                                Assigned To
                            </label>
                            <select
                                value={ticket.assigned_to?.id || ""}
                                onChange={(event) =>
                                    updateTicketAssignee(event.target.value)
                                }
                                disabled={saving}
                            >
                                <option value="">Unassigned</option>
                                {assignees.map((admin) => (
                                    <option value={admin.id} key={admin.id}>
                                        {admin.name}
                                    </option>
                                ))}
                            </select>

                            <div className="ats-property-row">
                                <span>Category</span>
                                <strong>{ticket.category?.name || "—"}</strong>
                            </div>

                            <div className="ats-property-row">
                                <span>Last Reply</span>
                                <strong>
                                    {formatDate(ticket.last_reply_at)}
                                </strong>
                            </div>

                            <div className="ats-property-row">
                                <span>Resolved At</span>
                                <strong>
                                    {formatDate(ticket.resolved_at)}
                                </strong>
                            </div>

                            <div className="ats-property-row">
                                <span>Closed At</span>
                                <strong>{formatDate(ticket.closed_at)}</strong>
                            </div>
                        </div>

                        <div className="ats-panel ats-requester-panel">
                            <h3>Requester Details</h3>
                            <div className="ats-requester-avatar">
                                <UserRound size={21} />
                            </div>
                            <strong>
                                {ticket.requester?.name || "Unknown requester"}
                            </strong>
                            <span>
                                {ticket.requester?.email ||
                                    "No email available"}
                            </span>
                            <small>
                                {requesterTypeLabel(ticket.requester?.type)}
                            </small>
                        </div>
                    </aside>
                </div>
            </section>
        );
    };

    /**
     * Renders the category management dialog.
     */
    const renderCategoryDialog = () => {
        if (!showCategoryForm) return null;

        return (
            <div className="ats-modal-backdrop">
                <div className="ats-modal">
                    <div className="ats-modal-heading">
                        <div>
                            <h3>
                                {editingCategory
                                    ? "Edit Category"
                                    : "Add Category"}
                            </h3>
                            <p>Manage support ticket categories.</p>
                        </div>

                        <button
                            type="button"
                            onClick={() => setShowCategoryForm(false)}
                            aria-label="Close"
                        >
                            <X size={19} />
                        </button>
                    </div>

                    <form onSubmit={saveCategory}>
                        <label className="ats-field-label">Category Name</label>
                        <input
                            name="name"
                            value={categoryForm.name}
                            onChange={handleCategoryFieldChange}
                            required
                            maxLength={150}
                        />

                        <label className="ats-field-label">Description</label>
                        <textarea
                            name="description"
                            value={categoryForm.description}
                            onChange={handleCategoryFieldChange}
                            rows={3}
                        />

                        <label className="ats-field-label">Sort Order</label>
                        <input
                            type="number"
                            name="sort_order"
                            min="0"
                            value={categoryForm.sort_order}
                            onChange={handleCategoryFieldChange}
                        />

                        <label className="ats-checkbox-label">
                            <input
                                type="checkbox"
                                name="is_active"
                                checked={categoryForm.is_active}
                                onChange={handleCategoryFieldChange}
                            />
                            Active category
                        </label>

                        <div className="ats-modal-actions">
                            <button
                                type="button"
                                className="ats-secondary-button"
                                onClick={() => setShowCategoryForm(false)}
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                className="ats-primary-button"
                                disabled={saving}
                            >
                                {saving ? "Saving..." : "Save Category"}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        );
    };

    /**
     * Renders the Admin Support dashboard and ticket listing.
     */
    const renderDashboard = () => (
        <>
            <div className="ats-page-header">
                <div>
                    {/* Navigate back to the Admin dashboard. */}
                    <a href="/admin" className="ats-back-link">
                        <ArrowLeft size={15} />
                        Dashboard
                    </a>

                    <span className="ats-eyebrow">ADMIN WORKSPACE</span>
                    <h1>Support & Tickets</h1>
                    <p>Manage support requests from all YouthHub modules.</p>
                </div>

                <div className="ats-header-actions">
                    <button
                        type="button"
                        className="ats-secondary-button"
                        onClick={loadDashboard}
                        disabled={loading}
                    >
                        <RefreshCw size={15} />
                        Refresh
                    </button>
                    <button
                        type="button"
                        className="ats-primary-button"
                        onClick={startNewCategory}
                    >
                        <Plus size={16} />
                        Add Category
                    </button>
                </div>
            </div>

            <div className="ats-stats-grid">
                {renderStat("Total Tickets", summary.total, Ticket, "blue")}
                {renderStat("Open", summary.open, CircleHelp, "orange")}
                {renderStat("Pending", summary.pending, Clock3, "purple")}
                {renderStat(
                    "Resolved",
                    summary.resolved,
                    CheckCircle2,
                    "green",
                )}
                {renderStat("Closed", summary.closed, X, "gray")}
                {renderStat("Urgent", summary.urgent, AlertCircle, "red")}
                {renderStat(
                    "Unassigned",
                    summary.unassigned,
                    UserRound,
                    "navy",
                )}
            </div>

            <div className="ats-panel ats-list-panel">
                <div className="ats-panel-heading ats-list-heading">
                    <div>
                        <h3>All Support Tickets</h3>
                        <p>Search, filter and manage incoming requests.</p>
                    </div>

                    <button
                        type="button"
                        className="ats-text-button"
                        onClick={() => {
                            setSearch("");
                            setStatus("all");
                            setPriority("all");
                            setCategoryId("all");
                            setAssignedTo("all");
                        }}
                    >
                        <Filter size={14} />
                        Clear filters
                    </button>
                </div>

                <div className="ats-filter-row">
                    <div className="ats-search-box">
                        <Search size={16} />
                        <input
                            value={search}
                            onChange={handleSearchChange}
                            onKeyDown={(event) => {
                                if (event.key === "Enter") applyFilters();
                            }}
                            placeholder="Search ticket number, subject..."
                        />
                    </div>

                    <select value={status} onChange={handleStatusChange}>
                        <option value="all">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>

                    <select value={priority} onChange={handlePriorityChange}>
                        <option value="all">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>

                    <select value={categoryId} onChange={handleCategoryChange}>
                        <option value="all">All Categories</option>
                        {categories.map((category) => (
                            <option value={category.id} key={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>

                    <select value={assignedTo} onChange={handleAssigneeChange}>
                        <option value="all">All Assignees</option>
                        <option value="unassigned">Unassigned</option>
                        {assignees.map((admin) => (
                            <option value={admin.id} key={admin.id}>
                                {admin.name}
                            </option>
                        ))}
                    </select>

                    <button
                        type="button"
                        className="ats-primary-button ats-filter-button"
                        onClick={applyFilters}
                        disabled={loading}
                    >
                        Apply
                    </button>
                </div>

                {error && (
                    <div className="ats-error-message">
                        <AlertCircle size={16} />
                        {error}
                    </div>
                )}

                <div className="ats-table-wrap">
                    <table className="ats-ticket-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Requester</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                <tr>
                                    <td
                                        colSpan="8"
                                        className="ats-table-message"
                                    >
                                        <LoaderCircle
                                            className="ats-spin"
                                            size={22}
                                        />
                                        Loading tickets...
                                    </td>
                                </tr>
                            ) : tickets.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan="8"
                                        className="ats-table-message"
                                    >
                                        No support tickets found.
                                    </td>
                                </tr>
                            ) : (
                                tickets.map((ticket) => (
                                    <tr key={ticket.id}>
                                        <td>
                                            <button
                                                type="button"
                                                className="ats-ticket-link"
                                                onClick={() =>
                                                    openTicket(ticket.id)
                                                }
                                            >
                                                {ticket.ticket_number}
                                            </button>
                                            <span className="ats-ticket-subject">
                                                {ticket.subject}
                                            </span>
                                        </td>

                                        <td>
                                            <strong>
                                                {ticket.requester?.name || "—"}
                                            </strong>
                                            <span className="ats-cell-subtext">
                                                {requesterTypeLabel(
                                                    ticket.requester?.type,
                                                )}
                                            </span>
                                        </td>

                                        <td>{ticket.category?.name || "—"}</td>

                                        <td>
                                            <span
                                                className={priorityClass(
                                                    ticket.priority,
                                                )}
                                            >
                                                {ticket.priority}
                                            </span>
                                        </td>

                                        <td>
                                            <span
                                                className={statusClass(
                                                    ticket.status,
                                                )}
                                            >
                                                {ticket.status}
                                            </span>
                                        </td>

                                        <td>
                                            {ticket.assigned_to?.name || (
                                                <span className="ats-unassigned">
                                                    Unassigned
                                                </span>
                                            )}
                                        </td>

                                        <td>{formatDate(ticket.created_at)}</td>

                                        <td>
                                            <button
                                                type="button"
                                                className="ats-view-button"
                                                onClick={() =>
                                                    openTicket(ticket.id)
                                                }
                                            >
                                                View
                                                <ChevronRight size={14} />
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="ats-pagination">
                    <span>
                        Showing{" "}
                        {tickets.length
                            ? (pagination.current_page - 1) *
                                  pagination.per_page +
                              1
                            : 0}{" "}
                        to{" "}
                        {(pagination.current_page - 1) * pagination.per_page +
                            tickets.length}{" "}
                        of {pagination.total} tickets
                    </span>

                    <div>
                        <button
                            type="button"
                            onClick={() =>
                                handlePageChange(pagination.current_page - 1)
                            }
                            disabled={loading || pagination.current_page <= 1}
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>

                        <span>
                            {pagination.current_page} / {pagination.last_page}
                        </span>

                        <button
                            type="button"
                            onClick={() =>
                                handlePageChange(pagination.current_page + 1)
                            }
                            disabled={
                                loading ||
                                pagination.current_page >= pagination.last_page
                            }
                            aria-label="Next page"
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </div>
            </div>

            <div className="ats-panel ats-category-panel">
                <div className="ats-panel-heading">
                    <div>
                        <h3>Support Categories</h3>
                        <p>Manage the categories available to requesters.</p>
                    </div>
                </div>

                <div className="ats-category-grid">
                    {categories.map((category) => (
                        <div className="ats-category-item" key={category.id}>
                            <div>
                                <strong>{category.name}</strong>
                                <span>
                                    {category.description || "No description"}
                                </span>
                                <small>
                                    {category.is_active ? "Active" : "Inactive"}
                                    {" · "}Order {category.sort_order}
                                </small>
                            </div>

                            <button
                                type="button"
                                className="ats-edit-category"
                                onClick={() => startEditCategory(category)}
                            >
                                Edit
                            </button>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );

    return (
        <div className="ats-page">
            {error && (
                <div className="ats-error-message ats-detail-error">
                    <AlertCircle size={16} />
                    {error}
                    <button
                        type="button"
                        onClick={() => setError("")}
                        aria-label="Dismiss error"
                    >
                        <X size={15} />
                    </button>
                </div>
            )}

            {selectedTicket || detailLoading
                ? renderTicketDetails()
                : renderDashboard()}

            {renderCategoryDialog()}
        </div>
    );
}
