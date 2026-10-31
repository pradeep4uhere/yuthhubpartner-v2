import React, { useCallback, useEffect, useState } from "react";
import {
    LifeBuoy,
    Plus,
    ArrowLeft,
    RefreshCw,
    Search,
    MessageSquare,
    Send,
    Clock3,
    CheckCircle2,
    AlertCircle,
    Ticket,
    X,
    ChevronRight,
    Paperclip,
    CircleHelp,
    FileText,
    Download,
    Image as ImageIcon,
    Trash2,
} from "lucide-react";

import "./PlacementSupportTicketsPage.css";

const API_BASE = "/api/partner-placement";
const MAX_FILES = 5;
const MAX_FILE_SIZE = 5 * 1024 * 1024;
const ALLOWED_EXTENSIONS = [
    "jpg",
    "jpeg",
    "png",
    "pdf",
    "doc",
    "docx",
    "xls",
    "xlsx",
    "csv",
];
const ACCEPTED_FILES = `.${ALLOWED_EXTENSIONS.join(",.")}`;

/**
 * Format a date using the current browser locale.
 *
 * @param {string|null} value
 * @returns {string}
 */
function formatDate(value) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "—";

    return new Intl.DateTimeFormat(
        document.documentElement.lang || navigator.language || "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        },
    ).format(date);
}

/**
 * Format a file size for display.
 *
 * @param {number} bytes
 * @returns {string}
 */
function formatFileSize(bytes) {
    const size = Number(bytes) || 0;

    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;

    return `${(size / (1024 * 1024)).toFixed(2)} MB`;
}

/**
 * Validate selected files before sending them to the API.
 *
 * @param {File[]} files
 * @returns {string|null}
 */
function validateFiles(files) {
    if (files.length > MAX_FILES) {
        return `You can attach up to ${MAX_FILES} files at a time.`;
    }

    for (const file of files) {
        const extension = file.name.split(".").pop()?.toLowerCase();

        if (!extension || !ALLOWED_EXTENSIONS.includes(extension)) {
            return `File "${file.name}" has an unsupported format.`;
        }

        if (file.size > MAX_FILE_SIZE) {
            return `File "${file.name}" exceeds the 5 MB limit.`;
        }
    }

    return null;
}

/**
 * Send an authenticated request to the Placement Partner support API.
 *
 * FormData requests must not manually set Content-Type because the browser
 * adds the multipart boundary. JSON requests continue using JSON headers.
 *
 * @param {string} path
 * @param {RequestInit} options
 * @returns {Promise<object>}
 */
async function apiRequest(path, options = {}) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    const isMultipart =
        typeof FormData !== "undefined" && options.body instanceof FormData;

    const headers = {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        ...(options.body && !isMultipart
            ? { "Content-Type": "application/json" }
            : {}),
        ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
        ...options.headers,
    };

    const response = await fetch(`${API_BASE}${path}`, {
        ...options,
        headers,
        credentials: "same-origin",
    });

    let result = {};

    try {
        result = await response.json();
    } catch {
        // The server may return an empty or non-JSON response.
    }

    if (!response.ok) {
        const validationErrors = result.errors
            ? Object.values(result.errors).flat().join(" ")
            : "";

        throw new Error(
            validationErrors ||
                result.message ||
                `Request failed (${response.status}).`,
        );
    }

    return result;
}

/**
 * Display a ticket status badge.
 *
 * @param {{status: string, tr: Function}} props
 * @returns {JSX.Element}
 */
function StatusBadge({ status, tr }) {
    const normalized = String(status || "open").toLowerCase();

    const statusKeys = {
        open: "support_status_open",
        pending: "support_status_pending",
        in_progress: "support_status_in_progress",
        resolved: "support_status_resolved",
        closed: "support_status_closed",
    };

    return (
        <span
            className={`st-status st-status--${normalized.replaceAll("_", "-")}`}
        >
            <span className="st-status__dot" />
            {tr(statusKeys[normalized] || "support_status_open", normalized)}
        </span>
    );
}

/**
 * Display a ticket priority badge.
 *
 * @param {{priority: string, tr: Function}} props
 * @returns {JSX.Element}
 */
function PriorityBadge({ priority, tr }) {
    const normalized = String(priority || "normal").toLowerCase();

    const priorityKeys = {
        low: "support_priority_low",
        normal: "support_priority_normal",
        high: "support_priority_high",
        urgent: "support_priority_urgent",
    };

    return (
        <span className={`st-priority st-priority--${normalized}`}>
            {tr(
                priorityKeys[normalized] || "support_priority_normal",
                normalized,
            )}
        </span>
    );
}

/**
 * Display selected local files with size and remove controls.
 *
 * @param {{files: File[], onRemove: Function, disabled?: boolean, tr: Function}} props
 * @returns {JSX.Element|null}
 */
function SelectedFiles({ files, onRemove, disabled = false, tr }) {
    if (!files.length) return null;

    return (
        <div className="st-selected-files">
            {files.map((file, index) => {
                const isImage = file.type.startsWith("image/");

                return (
                    <div
                        className="st-selected-file"
                        key={`${file.name}-${file.size}-${index}`}
                    >
                        <span className="st-selected-file__icon">
                            {isImage ? (
                                <ImageIcon size={19} />
                            ) : (
                                <FileText size={19} />
                            )}
                        </span>

                        <span className="st-selected-file__details">
                            <strong title={file.name}>{file.name}</strong>
                            <small>{formatFileSize(file.size)}</small>
                        </span>

                        <button
                            type="button"
                            className="st-file-remove"
                            onClick={() => onRemove(index)}
                            disabled={disabled}
                            aria-label={`${tr("support_remove_file", "Remove file")}: ${file.name}`}
                            title={tr("support_remove_file", "Remove file")}
                        >
                            <Trash2 size={16} />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}

/**
 * Render attachment links returned by the API.
 *
 * Download URLs should point to the authenticated backend download endpoint.
 *
 * @param {{attachments?: object[], tr: Function}} props
 * @returns {JSX.Element|null}
 */
function AttachmentList({ attachments = [], tr }) {
    if (!Array.isArray(attachments) || attachments.length === 0) {
        return null;
    }

    return (
        <div className="st-attachment-list">
            <strong className="st-attachment-list__heading">
                <Paperclip size={15} />
                {tr("support_attachments", "Attachments")}
            </strong>

            {attachments.map((attachment) => {
                const name = attachment.original_name || "Attachment";
                const isImage = String(attachment.mime_type || "").startsWith(
                    "image/",
                );

                return (
                    <a
                        key={attachment.id}
                        className="st-attachment-item"
                        href={attachment.download_url}
                        target="_blank"
                        rel="noreferrer"
                        title={name}
                    >
                        <span className="st-attachment-item__icon">
                            {isImage ? (
                                <ImageIcon size={19} />
                            ) : (
                                <FileText size={19} />
                            )}
                        </span>

                        <span className="st-attachment-item__details">
                            <strong>{name}</strong>
                            <small>{formatFileSize(attachment.size)}</small>
                        </span>

                        <Download size={17} />
                    </a>
                );
            })}
        </div>
    );
}

/**
 * Render a file picker and the user's selected files.
 *
 * @param {{
 *   id: string,
 *   files: File[],
 *   setFiles: Function,
 *   disabled?: boolean,
 *   tr: Function,
 *   onError: Function
 * }} props
 * @returns {JSX.Element}
 */
function AttachmentPicker({
    id,
    files,
    setFiles,
    disabled = false,
    tr,
    onError,
}) {
    /**
     * Add selected files while enforcing the count, size and extension limits.
     *
     * @param {React.ChangeEvent<HTMLInputElement>} event
     */
    const handleFileSelection = (event) => {
        const incoming = Array.from(event.target.files || []);
        event.target.value = "";

        if (!incoming.length) return;

        const combined = [...files, ...incoming];
        const error = validateFiles(combined);

        if (error) {
            onError(error);
            return;
        }

        setFiles(combined);
        onError("");
    };

    /**
     * Remove a file by its index from the selection.
     *
     * @param {number} index
     */
    const removeFile = (index) => {
        setFiles((previous) => previous.filter((_, i) => i !== index));
        onError("");
    };

    return (
        <div className="st-form-field st-attachment-picker">
            <label htmlFor={id}>
                <Paperclip size={16} />
                {tr("support_attachments", "Attachments")}
                <small>{tr("support_optional", "Optional")}</small>
            </label>

            <input
                id={id}
                type="file"
                multiple
                accept={ACCEPTED_FILES}
                onChange={handleFileSelection}
                disabled={disabled || files.length >= MAX_FILES}
            />

            <small className="st-field-hint">
                {tr(
                    "support_attachment_hint",
                    "Up to 5 files, 5 MB each. JPG, PNG, PDF, DOC, DOCX, XLS, XLSX or CSV.",
                )}
            </small>

            <SelectedFiles
                files={files}
                onRemove={removeFile}
                disabled={disabled}
                tr={tr}
            />
        </div>
    );
}

/**
 * Render the Placement Partner's support ticket workspace.
 *
 * Supports ticket creation, ticket listing, ticket details, replies and
 * file attachments. All user-facing labels use the dashboard translator.
 *
 * @param {{t?: Function}} props
 * @returns {JSX.Element}
 */
export default function PlacementSupportTicketsPage({ t }) {
    /**
     * Resolve a translation key and fall back when no translation exists.
     *
     * @param {string} key
     * @param {string} fallback
     * @returns {string}
     */
    const tr = useCallback(
        (key, fallback = key) => {
            if (typeof t !== "function") return fallback;

            const value = t(key);

            return !value || value === key ? fallback : value;
        },
        [t],
    );

    const [tickets, setTickets] = useState([]);
    const [categories, setCategories] = useState([]);
    const [pagination, setPagination] = useState({
        current_page: 1,
        last_page: 1,
        total: 0,
    });

    const [view, setView] = useState("list");
    const [selectedTicket, setSelectedTicket] = useState(null);
    const [searchTerm, setSearchTerm] = useState("");

    const [loading, setLoading] = useState(true);
    const [detailLoading, setDetailLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [replySubmitting, setReplySubmitting] = useState(false);

    const [pageError, setPageError] = useState("");
    const [formError, setFormError] = useState("");
    const [successMessage, setSuccessMessage] = useState("");
    const [ticketFileError, setTicketFileError] = useState("");
    const [replyFileError, setReplyFileError] = useState("");

    const [ticketFiles, setTicketFiles] = useState([]);
    const [replyFiles, setReplyFiles] = useState([]);

    const [form, setForm] = useState({
        category_id: "",
        subject: "",
        description: "",
        affected_module: "",
        priority: "normal",
    });

    const [replyMessage, setReplyMessage] = useState("");

    /**
     * Load active categories and the authenticated user's tickets.
     */
    const loadInitialData = useCallback(async () => {
        setLoading(true);
        setPageError("");

        try {
            const [categoryResult, ticketResult] = await Promise.all([
                apiRequest("/support-ticket-categories"),
                apiRequest("/support-tickets"),
            ]);

            setCategories(
                Array.isArray(categoryResult.data) ? categoryResult.data : [],
            );

            setTickets(
                Array.isArray(ticketResult.data) ? ticketResult.data : [],
            );

            setPagination({
                current_page: ticketResult.meta?.current_page || 1,
                last_page: ticketResult.meta?.last_page || 1,
                total: ticketResult.meta?.total || 0,
            });
        } catch (error) {
            console.error("Support tickets loading error:", error);
            setPageError(
                error.message ||
                    tr("support_error_load_tickets", "Unable to load tickets."),
            );
        } finally {
            setLoading(false);
        }
    }, [tr]);

    useEffect(() => {
        loadInitialData();
    }, [loadInitialData]);

    /**
     * Open a ticket and retrieve its public conversation and attachments.
     *
     * @param {number|string} ticketId
     */
    const openTicket = async (ticketId) => {
        setView("detail");
        setDetailLoading(true);
        setSelectedTicket(null);
        setPageError("");
        setSuccessMessage("");
        setReplyMessage("");
        setReplyFiles([]);
        setReplyFileError("");

        try {
            const result = await apiRequest(`/support-tickets/${ticketId}`);
            setSelectedTicket(result.data);
        } catch (error) {
            console.error("Support ticket detail error:", error);
            setPageError(
                error.message ||
                    tr(
                        "support_error_load_details",
                        "Unable to load ticket details.",
                    ),
            );
            setView("list");
        } finally {
            setDetailLoading(false);
        }
    };

    /**
     * Create a support ticket and upload its attachments in one multipart request.
     *
     * @param {React.FormEvent<HTMLFormElement>} event
     */
    const handleCreateTicket = async (event) => {
        event.preventDefault();
        setFormError("");
        setTicketFileError("");
        setSuccessMessage("");

        if (!form.category_id) {
            setFormError(
                tr(
                    "support_validation_category",
                    "Please select a support category.",
                ),
            );
            return;
        }

        const fileError = validateFiles(ticketFiles);

        if (fileError) {
            setTicketFileError(fileError);
            return;
        }

        setSubmitting(true);

        try {
            const payload = new FormData();

            payload.append("category_id", String(Number(form.category_id)));
            payload.append("subject", form.subject);
            payload.append("description", form.description);
            payload.append("affected_module", form.affected_module || "");
            payload.append("priority", form.priority);

            ticketFiles.forEach((file) => {
                payload.append("attachments[]", file);
            });

            const result = await apiRequest("/support-tickets", {
                method: "POST",
                body: payload,
            });

            setForm({
                category_id: "",
                subject: "",
                description: "",
                affected_module: "",
                priority: "normal",
            });

            setTicketFiles([]);
            setTicketFileError("");

            await loadInitialData();

            if (result.data?.id) {
                await openTicket(result.data.id);
            } else {
                setView("list");
            }

            setSuccessMessage(
                result.message ||
                    tr(
                        "support_success_created",
                        "Support ticket created successfully.",
                    ),
            );
        } catch (error) {
            console.error("Support ticket creation error:", error);
            setFormError(
                error.message ||
                    tr(
                        "support_error_create_ticket",
                        "Unable to create ticket.",
                    ),
            );
        } finally {
            setSubmitting(false);
        }
    };

    /**
     * Submit a public reply with optional file attachments.
     *
     * The backend must allow an empty message when attachments are present.
     *
     * @param {React.FormEvent<HTMLFormElement>} event
     */
    const handleReply = async (event) => {
        event.preventDefault();

        if (!selectedTicket?.id) return;
        if (!replyMessage.trim() && replyFiles.length === 0) return;

        const fileError = validateFiles(replyFiles);

        if (fileError) {
            setReplyFileError(fileError);
            return;
        }

        setReplySubmitting(true);
        setPageError("");
        setReplyFileError("");
        setSuccessMessage("");

        try {
            const payload = new FormData();

            payload.append("message", replyMessage.trim());

            replyFiles.forEach((file) => {
                payload.append("attachments[]", file);
            });

            const ticketId = selectedTicket.id;

            const result = await apiRequest(
                `/support-tickets/${ticketId}/replies`,
                {
                    method: "POST",
                    body: payload,
                },
            );

            setReplyMessage("");
            setReplyFiles([]);
            setReplyFileError("");

            await openTicket(ticketId);
            await loadInitialData();

            setSuccessMessage(
                result.message ||
                    tr("support_success_reply", "Reply added successfully."),
            );
        } catch (error) {
            console.error("Support ticket reply error:", error);
            setPageError(
                error.message ||
                    tr("support_error_reply", "Unable to send reply."),
            );
        } finally {
            setReplySubmitting(false);
        }
    };

    const filteredTickets = tickets.filter((ticket) => {
        const query = searchTerm.trim().toLowerCase();

        if (!query) return true;

        return [
            ticket.ticket_number,
            ticket.subject,
            ticket.category?.name,
            ticket.status,
            ticket.priority,
        ].some((value) =>
            String(value || "")
                .toLowerCase()
                .includes(query),
        );
    });

    const openCount = tickets.filter((ticket) =>
        ["open", "pending", "in_progress"].includes(
            String(ticket.status).toLowerCase(),
        ),
    ).length;

    const resolvedCount = tickets.filter((ticket) =>
        ["resolved", "closed"].includes(String(ticket.status).toLowerCase()),
    ).length;

    /**
     * Return to the ticket list and clear transient messages.
     */
    const showList = () => {
        setView("list");
        setSelectedTicket(null);
        setPageError("");
        setFormError("");
        setTicketFileError("");
        setReplyFileError("");
        setSuccessMessage("");
    };

    /**
     * Open a clean ticket creation form.
     */
    const showCreateForm = () => {
        setView("create");
        setSelectedTicket(null);
        setPageError("");
        setFormError("");
        setTicketFileError("");
        setReplyFileError("");
        setTicketFiles([]);
        setReplyFiles([]);
        setSuccessMessage("");
    };

    const ticketIsClosed =
        String(selectedTicket?.status || "").toLowerCase() === "closed";

    return (
        <section className="st-page">
            <header className="st-hero">
                <div className="st-hero__content">
                    <div className="st-hero__icon">
                        <LifeBuoy size={27} />
                    </div>

                    <div className="st-hero__text">
                        <span className="st-eyebrow">
                            {tr("support_eyebrow", "PLACEMENT PARTNER PORTAL")}
                        </span>

                        <h1>
                            {view === "create"
                                ? tr("support_create_title", "Create a Ticket")
                                : view === "detail"
                                  ? tr(
                                        "support_conversation_title",
                                        "Ticket Conversation",
                                    )
                                  : tr("support_page_title", "Support & Help")}
                        </h1>

                        <p>
                            {view === "create"
                                ? tr(
                                      "support_create_subtitle",
                                      "Tell us what you need help with. Our team is here for you.",
                                  )
                                : view === "detail"
                                  ? tr(
                                        "support_conversation_subtitle",
                                        "View updates and communicate with our support team.",
                                    )
                                  : tr(
                                        "support_page_subtitle",
                                        "Get help, report an issue, and track your support requests.",
                                    )}
                        </p>
                    </div>
                </div>

                <div className="st-hero__actions">
                    {view === "list" && (
                        <>
                            <button
                                type="button"
                                className="st-button st-button--light"
                                onClick={loadInitialData}
                                disabled={loading}
                            >
                                <RefreshCw
                                    size={17}
                                    className={loading ? "st-spin" : ""}
                                />
                                <span>{tr("support_refresh", "Refresh")}</span>
                            </button>

                            <button
                                type="button"
                                className="st-button st-button--primary"
                                onClick={showCreateForm}
                            >
                                <Plus size={18} />
                                {tr("support_new_ticket", "New Ticket")}
                            </button>
                        </>
                    )}

                    {view !== "list" && (
                        <button
                            type="button"
                            className="st-button st-button--light"
                            onClick={showList}
                        >
                            <ArrowLeft size={17} />
                            {tr("support_back_to_tickets", "Back to Tickets")}
                        </button>
                    )}
                </div>
            </header>

            {successMessage && (
                <div className="st-alert st-alert--success" role="status">
                    <CheckCircle2 size={19} />
                    <span>{successMessage}</span>
                    <button
                        type="button"
                        aria-label={tr("support_dismiss", "Dismiss")}
                        onClick={() => setSuccessMessage("")}
                    >
                        <X size={17} />
                    </button>
                </div>
            )}

            {pageError && (
                <div className="st-alert st-alert--error" role="alert">
                    <AlertCircle size={19} />
                    <span>{pageError}</span>
                    <button
                        type="button"
                        aria-label={tr("support_dismiss", "Dismiss")}
                        onClick={() => setPageError("")}
                    >
                        <X size={17} />
                    </button>
                </div>
            )}

            {view === "list" && (
                <>
                    <div className="st-stats-grid">
                        <article className="st-stat-card">
                            <span className="st-stat-card__icon st-stat-card__icon--blue">
                                <Ticket size={22} />
                            </span>
                            <div>
                                <span>
                                    {tr(
                                        "support_total_tickets",
                                        "Total Tickets",
                                    )}
                                </span>
                                <strong>{pagination.total}</strong>
                                <small>
                                    {tr(
                                        "support_all_requests",
                                        "All your requests",
                                    )}
                                </small>
                            </div>
                        </article>

                        <article className="st-stat-card">
                            <span className="st-stat-card__icon st-stat-card__icon--amber">
                                <Clock3 size={22} />
                            </span>
                            <div>
                                <span>
                                    {tr(
                                        "support_active_tickets",
                                        "Active Tickets",
                                    )}
                                </span>
                                <strong>{openCount}</strong>
                                <small>
                                    {tr(
                                        "support_awaiting_attention",
                                        "Awaiting attention",
                                    )}
                                </small>
                            </div>
                        </article>

                        <article className="st-stat-card">
                            <span className="st-stat-card__icon st-stat-card__icon--green">
                                <CheckCircle2 size={22} />
                            </span>
                            <div>
                                <span>
                                    {tr("support_resolved_tickets", "Resolved")}
                                </span>
                                <strong>{resolvedCount}</strong>
                                <small>
                                    {tr(
                                        "support_completed_requests",
                                        "Completed requests",
                                    )}
                                </small>
                            </div>
                        </article>
                    </div>

                    <div className="st-panel">
                        <div className="st-panel__heading">
                            <div>
                                <h2>
                                    {tr("support_my_tickets", "My Tickets")}
                                </h2>
                                <p>
                                    {tr(
                                        "support_manage_subtitle",
                                        "Manage and track your support requests in one place.",
                                    )}
                                </p>
                            </div>

                            <span className="st-ticket-count">
                                {pagination.total}{" "}
                                {tr("support_tickets_label", "tickets")}
                            </span>
                        </div>

                        <div className="st-toolbar">
                            <label className="st-search">
                                <Search size={18} />
                                <input
                                    type="search"
                                    value={searchTerm}
                                    onChange={(event) =>
                                        setSearchTerm(event.target.value)
                                    }
                                    placeholder={tr(
                                        "support_search_placeholder",
                                        "Search by ticket number, subject or status...",
                                    )}
                                />
                            </label>

                            <button
                                type="button"
                                className="st-button st-button--outline"
                                onClick={showCreateForm}
                            >
                                <Plus size={17} />
                                {tr("support_create_ticket", "Create Ticket")}
                            </button>
                        </div>

                        {loading ? (
                            <div className="st-loading">
                                <span className="st-spinner" />
                                <p>
                                    {tr(
                                        "support_loading_tickets",
                                        "Loading your tickets...",
                                    )}
                                </p>
                            </div>
                        ) : filteredTickets.length === 0 ? (
                            <div className="st-empty">
                                <div className="st-empty__icon">
                                    <MessageSquare size={28} />
                                </div>
                                <h3>
                                    {searchTerm
                                        ? tr(
                                              "support_no_search_results",
                                              "No matching tickets",
                                          )
                                        : tr(
                                              "support_no_tickets",
                                              "No support tickets yet",
                                          )}
                                </h3>
                                <p>
                                    {searchTerm
                                        ? tr(
                                              "support_try_another_search",
                                              "Try a different search term.",
                                          )
                                        : tr(
                                              "support_no_tickets_description",
                                              "If you need assistance, create a ticket and our team will help you.",
                                          )}
                                </p>

                                {!searchTerm && (
                                    <button
                                        type="button"
                                        className="st-button st-button--primary"
                                        onClick={showCreateForm}
                                    >
                                        <Plus size={18} />
                                        {tr(
                                            "support_create_first_ticket",
                                            "Create Your First Ticket",
                                        )}
                                    </button>
                                )}
                            </div>
                        ) : (
                            <div className="st-ticket-list">
                                {filteredTickets.map((ticket) => (
                                    <button
                                        type="button"
                                        className="st-ticket-row"
                                        key={ticket.id}
                                        onClick={() => openTicket(ticket.id)}
                                    >
                                        <span className="st-ticket-row__icon">
                                            <MessageSquare size={21} />
                                        </span>

                                        <span className="st-ticket-row__main">
                                            <span className="st-ticket-row__meta">
                                                <strong>
                                                    {ticket.ticket_number}
                                                </strong>
                                                <span className="st-ticket-row__separator">
                                                    •
                                                </span>
                                                <span>
                                                    {ticket.category?.name ||
                                                        tr(
                                                            "support_uncategorized",
                                                            "Uncategorized",
                                                        )}
                                                </span>
                                            </span>

                                            <span className="st-ticket-row__subject">
                                                {ticket.subject}
                                            </span>

                                            <span className="st-ticket-row__date">
                                                <Clock3 size={13} />
                                                {tr(
                                                    "support_created",
                                                    "Created",
                                                )}{" "}
                                                {formatDate(ticket.created_at)}
                                            </span>
                                        </span>

                                        <span className="st-ticket-row__badges">
                                            <PriorityBadge
                                                priority={ticket.priority}
                                                tr={tr}
                                            />
                                            <StatusBadge
                                                status={ticket.status}
                                                tr={tr}
                                            />
                                        </span>

                                        <ChevronRight
                                            size={19}
                                            className="st-ticket-row__chevron"
                                        />
                                    </button>
                                ))}
                            </div>
                        )}

                        {!loading && pagination.total > 0 && (
                            <div className="st-pagination-note">
                                {tr("support_showing", "Showing")}{" "}
                                {filteredTickets.length}{" "}
                                {tr("support_of", "of")} {pagination.total}{" "}
                                {tr("support_tickets_label", "tickets")}
                                {pagination.last_page > 1 && (
                                    <span>
                                        {" "}
                                        · {tr("support_page", "Page")}{" "}
                                        {pagination.current_page}{" "}
                                        {tr("support_of", "of")}{" "}
                                        {pagination.last_page}
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                </>
            )}

            {view === "create" && (
                <div className="st-form-layout">
                    <div className="st-panel st-form-panel">
                        <div className="st-panel__heading">
                            <div>
                                <h2>
                                    {tr(
                                        "support_form_heading",
                                        "Tell us how we can help",
                                    )}
                                </h2>
                                <p>
                                    {tr(
                                        "support_form_description",
                                        "Complete the details below so our team can assist you.",
                                    )}
                                </p>
                            </div>
                            <span className="st-heading-icon">
                                <CircleHelp size={23} />
                            </span>
                        </div>

                        {formError && (
                            <div
                                className="st-alert st-alert--error"
                                role="alert"
                            >
                                <AlertCircle size={18} />
                                <span>{formError}</span>
                            </div>
                        )}

                        <form className="st-form" onSubmit={handleCreateTicket}>
                            <div className="st-form-field">
                                <label htmlFor="st-category">
                                    {tr("support_category", "Support Category")}{" "}
                                    <span>*</span>
                                </label>
                                <select
                                    id="st-category"
                                    value={form.category_id}
                                    onChange={(event) =>
                                        setForm({
                                            ...form,
                                            category_id: event.target.value,
                                        })
                                    }
                                    required
                                >
                                    <option value="">
                                        {tr(
                                            "support_select_category",
                                            "Select a category",
                                        )}
                                    </option>
                                    {categories.map((category) => (
                                        <option
                                            key={category.id}
                                            value={category.id}
                                        >
                                            {category.name}
                                        </option>
                                    ))}
                                </select>

                                {categories.length === 0 && !loading && (
                                    <small className="st-field-hint">
                                        {tr(
                                            "support_no_categories",
                                            "No active categories are available. Please contact your administrator.",
                                        )}
                                    </small>
                                )}
                            </div>

                            <div className="st-form-field">
                                <label htmlFor="st-subject">
                                    {tr("support_subject", "Subject")}{" "}
                                    <span>*</span>
                                </label>
                                <input
                                    id="st-subject"
                                    type="text"
                                    maxLength={200}
                                    value={form.subject}
                                    onChange={(event) =>
                                        setForm({
                                            ...form,
                                            subject: event.target.value,
                                        })
                                    }
                                    placeholder={tr(
                                        "support_subject_placeholder",
                                        "Briefly describe your issue",
                                    )}
                                    required
                                />
                            </div>

                            <div className="st-form-grid">
                                <div className="st-form-field">
                                    <label htmlFor="st-priority">
                                        {tr("support_priority", "Priority")}
                                    </label>
                                    <select
                                        id="st-priority"
                                        value={form.priority}
                                        onChange={(event) =>
                                            setForm({
                                                ...form,
                                                priority: event.target.value,
                                            })
                                        }
                                    >
                                        {[
                                            [
                                                "low",
                                                "support_priority_low",
                                                "Low",
                                            ],
                                            [
                                                "normal",
                                                "support_priority_normal",
                                                "Normal",
                                            ],
                                            [
                                                "high",
                                                "support_priority_high",
                                                "High",
                                            ],
                                            [
                                                "urgent",
                                                "support_priority_urgent",
                                                "Urgent",
                                            ],
                                        ].map(([value, key, fallback]) => (
                                            <option key={value} value={value}>
                                                {tr(key, fallback)}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="st-form-field">
                                    <label htmlFor="st-module">
                                        {tr(
                                            "support_affected_module",
                                            "Affected Module",
                                        )}
                                        <small>
                                            {tr("support_optional", "Optional")}
                                        </small>
                                    </label>
                                    <input
                                        id="st-module"
                                        type="text"
                                        maxLength={100}
                                        value={form.affected_module}
                                        onChange={(event) =>
                                            setForm({
                                                ...form,
                                                affected_module:
                                                    event.target.value,
                                            })
                                        }
                                        placeholder={tr(
                                            "support_module_placeholder",
                                            "e.g. Learners, Reports",
                                        )}
                                    />
                                </div>
                            </div>

                            <div className="st-form-field">
                                <label htmlFor="st-description">
                                    {tr(
                                        "support_description",
                                        "Describe your issue",
                                    )}{" "}
                                    <span>*</span>
                                </label>
                                <textarea
                                    id="st-description"
                                    rows={6}
                                    maxLength={20000}
                                    value={form.description}
                                    onChange={(event) =>
                                        setForm({
                                            ...form,
                                            description: event.target.value,
                                        })
                                    }
                                    placeholder={tr(
                                        "support_description_placeholder",
                                        "Explain what happened, what you expected, and any steps to reproduce the issue...",
                                    )}
                                    required
                                />
                                <div className="st-character-count">
                                    {form.description.length}/20000
                                </div>
                            </div>

                            <AttachmentPicker
                                id="st-ticket-attachments"
                                files={ticketFiles}
                                setFiles={setTicketFiles}
                                disabled={submitting}
                                tr={tr}
                                onError={setTicketFileError}
                            />

                            {ticketFileError && (
                                <div
                                    className="st-alert st-alert--error"
                                    role="alert"
                                >
                                    <AlertCircle size={18} />
                                    <span>{ticketFileError}</span>
                                </div>
                            )}

                            <div className="st-form-footer">
                                <p>
                                    <AlertCircle size={15} />
                                    {tr(
                                        "support_form_required_note",
                                        "Fields marked * are required.",
                                    )}
                                </p>

                                <div>
                                    <button
                                        type="button"
                                        className="st-button st-button--outline"
                                        onClick={showList}
                                        disabled={submitting}
                                    >
                                        {tr("support_cancel", "Cancel")}
                                    </button>

                                    <button
                                        type="submit"
                                        className="st-button st-button--primary"
                                        disabled={
                                            submitting ||
                                            categories.length === 0
                                        }
                                    >
                                        {submitting ? (
                                            <>
                                                <span className="st-button-spinner" />
                                                {tr(
                                                    "support_submitting",
                                                    "Submitting...",
                                                )}
                                            </>
                                        ) : (
                                            <>
                                                <Send size={17} />
                                                {tr(
                                                    "support_submit_ticket",
                                                    "Submit Ticket",
                                                )}
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <aside className="st-help-card">
                        <div className="st-help-card__icon">
                            <LifeBuoy size={24} />
                        </div>
                        <h3>{tr("support_need_help", "Need help?")}</h3>
                        <p>
                            {tr(
                                "support_help_card_description",
                                "Give us enough detail to understand your issue. Our support team will review your request and respond in this ticket.",
                            )}
                        </p>

                        <div className="st-help-tip">
                            <CheckCircle2 size={17} />
                            <span>
                                {tr(
                                    "support_tip_one",
                                    "Choose the category that best matches your issue.",
                                )}
                            </span>
                        </div>
                        <div className="st-help-tip">
                            <CheckCircle2 size={17} />
                            <span>
                                {tr(
                                    "support_tip_two",
                                    "Include the steps that led to the problem.",
                                )}
                            </span>
                        </div>
                        <div className="st-help-tip">
                            <CheckCircle2 size={17} />
                            <span>
                                {tr(
                                    "support_tip_three",
                                    "Never include your password or other sensitive credentials.",
                                )}
                            </span>
                        </div>
                    </aside>
                </div>
            )}

            {view === "detail" &&
                (detailLoading ? (
                    <div className="st-panel st-loading">
                        <span className="st-spinner" />
                        <p>
                            {tr(
                                "support_loading_details",
                                "Loading ticket details...",
                            )}
                        </p>
                    </div>
                ) : selectedTicket ? (
                    <div className="st-detail-layout">
                        <main className="st-panel st-conversation-panel">
                            <div className="st-detail-heading">
                                <div className="st-detail-heading__main">
                                    <span className="st-detail-ticket-number">
                                        {selectedTicket.ticket_number}
                                    </span>
                                    <h2>{selectedTicket.subject}</h2>
                                    <p>
                                        {tr("support_created", "Created")}{" "}
                                        {formatDate(selectedTicket.created_at)}
                                    </p>
                                </div>

                                <div className="st-detail-heading__badges">
                                    <StatusBadge
                                        status={selectedTicket.status}
                                        tr={tr}
                                    />
                                    <PriorityBadge
                                        priority={selectedTicket.priority}
                                        tr={tr}
                                    />
                                </div>
                            </div>

                            <div className="st-description-card">
                                <h3>
                                    {tr(
                                        "support_issue_description",
                                        "Issue Description",
                                    )}
                                </h3>
                                <p>{selectedTicket.description}</p>
                                <AttachmentList
                                    attachments={selectedTicket.attachments}
                                    tr={tr}
                                />
                            </div>

                            <div className="st-conversation-heading">
                                <div>
                                    <h3>
                                        {tr(
                                            "support_conversation",
                                            "Conversation",
                                        )}
                                    </h3>
                                    <p>
                                        {tr(
                                            "support_conversation_hint",
                                            "Replies from you and the support team appear here.",
                                        )}
                                    </p>
                                </div>
                                <span className="st-conversation-count">
                                    <MessageSquare size={15} />
                                    {selectedTicket.replies?.length || 0}
                                </span>
                            </div>

                            {!selectedTicket.replies?.length ? (
                                <div className="st-conversation-empty">
                                    <MessageSquare size={25} />
                                    <p>
                                        {tr(
                                            "support_no_replies",
                                            "No replies yet. Send a message below if you need to add more information.",
                                        )}
                                    </p>
                                </div>
                            ) : (
                                <div className="st-message-list">
                                    {selectedTicket.replies.map((reply) => {
                                        const isYou = reply.sender === "You";

                                        return (
                                            <article
                                                className={`st-message ${isYou ? "st-message--you" : "st-message--support"}`}
                                                key={reply.id}
                                            >
                                                <div className="st-message__avatar">
                                                    {isYou ? (
                                                        "Y"
                                                    ) : (
                                                        <LifeBuoy size={17} />
                                                    )}
                                                </div>

                                                <div className="st-message__content">
                                                    <div className="st-message__meta">
                                                        <strong>
                                                            {isYou
                                                                ? tr(
                                                                      "support_you",
                                                                      "You",
                                                                  )
                                                                : tr(
                                                                      "support_team",
                                                                      "Support Team",
                                                                  )}
                                                        </strong>
                                                        <time>
                                                            {formatDate(
                                                                reply.created_at,
                                                            )}
                                                        </time>
                                                    </div>

                                                    {reply.message && (
                                                        <div className="st-message__bubble">
                                                            {reply.message}
                                                        </div>
                                                    )}

                                                    <AttachmentList
                                                        attachments={
                                                            reply.attachments
                                                        }
                                                        tr={tr}
                                                    />
                                                </div>
                                            </article>
                                        );
                                    })}
                                </div>
                            )}

                            {ticketIsClosed ? (
                                <div className="st-closed-notice">
                                    <CheckCircle2 size={19} />
                                    <span>
                                        {tr(
                                            "support_ticket_closed_notice",
                                            "This ticket is closed and cannot receive new replies.",
                                        )}
                                    </span>
                                </div>
                            ) : (
                                <form
                                    className="st-reply-form"
                                    onSubmit={handleReply}
                                >
                                    <label htmlFor="st-reply">
                                        {tr("support_your_reply", "Your Reply")}
                                    </label>
                                    <textarea
                                        id="st-reply"
                                        rows={4}
                                        maxLength={20000}
                                        value={replyMessage}
                                        onChange={(event) =>
                                            setReplyMessage(event.target.value)
                                        }
                                        placeholder={tr(
                                            "support_reply_placeholder",
                                            "Write your message here...",
                                        )}
                                    />

                                    <AttachmentPicker
                                        id="st-reply-attachments"
                                        files={replyFiles}
                                        setFiles={setReplyFiles}
                                        disabled={replySubmitting}
                                        tr={tr}
                                        onError={setReplyFileError}
                                    />

                                    {replyFileError && (
                                        <div
                                            className="st-alert st-alert--error"
                                            role="alert"
                                        >
                                            <AlertCircle size={18} />
                                            <span>{replyFileError}</span>
                                        </div>
                                    )}

                                    <div className="st-reply-form__footer">
                                        <span>
                                            {tr(
                                                "support_reply_visibility",
                                                "Your reply will be visible to the support team.",
                                            )}
                                        </span>

                                        <button
                                            type="submit"
                                            className="st-button st-button--primary"
                                            disabled={
                                                replySubmitting ||
                                                (!replyMessage.trim() &&
                                                    replyFiles.length === 0)
                                            }
                                        >
                                            {replySubmitting ? (
                                                <>
                                                    <span className="st-button-spinner" />
                                                    {tr(
                                                        "support_sending",
                                                        "Sending...",
                                                    )}
                                                </>
                                            ) : (
                                                <>
                                                    <Send size={17} />
                                                    {tr(
                                                        "support_send_reply",
                                                        "Send Reply",
                                                    )}
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </form>
                            )}
                        </main>

                        <aside className="st-panel st-ticket-info">
                            <h3>
                                {tr(
                                    "support_ticket_information",
                                    "Ticket Information",
                                )}
                            </h3>

                            <div className="st-info-item">
                                <span>
                                    {tr(
                                        "support_ticket_number",
                                        "Ticket Number",
                                    )}
                                </span>
                                <strong>{selectedTicket.ticket_number}</strong>
                            </div>

                            <div className="st-info-item">
                                <span>
                                    {tr("support_category", "Category")}
                                </span>
                                <strong>
                                    {selectedTicket.category?.name || "—"}
                                </strong>
                            </div>

                            <div className="st-info-item">
                                <span>{tr("support_status", "Status")}</span>
                                <StatusBadge
                                    status={selectedTicket.status}
                                    tr={tr}
                                />
                            </div>

                            <div className="st-info-item">
                                <span>
                                    {tr("support_priority", "Priority")}
                                </span>
                                <PriorityBadge
                                    priority={selectedTicket.priority}
                                    tr={tr}
                                />
                            </div>

                            <div className="st-info-item">
                                <span>
                                    {tr(
                                        "support_affected_module",
                                        "Affected Module",
                                    )}
                                </span>
                                <strong>
                                    {selectedTicket.affected_module || "—"}
                                </strong>
                            </div>

                            <div className="st-info-item">
                                <span>
                                    {tr("support_created_on", "Created On")}
                                </span>
                                <strong>
                                    {formatDate(selectedTicket.created_at)}
                                </strong>
                            </div>

                            <div className="st-info-help">
                                <LifeBuoy size={21} />
                                <div>
                                    <strong>
                                        {tr(
                                            "support_need_more_help",
                                            "Need more help?",
                                        )}
                                    </strong>
                                    <p>
                                        {tr(
                                            "support_reply_hint",
                                            "Add a reply to this conversation to provide additional details.",
                                        )}
                                    </p>
                                </div>
                            </div>
                        </aside>
                    </div>
                ) : null)}
        </section>
    );
}
