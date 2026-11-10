import { useCallback, useEffect, useState } from "react";
import {
    Megaphone,
    Search,
    RefreshCw,
    ChevronLeft,
    ChevronRight,
    Eye,
    UsersRound,
    FileText,
    CheckCircle2,
    Clock3,
    AlertCircle,
    X,
    Upload,
    Save,
    Send,
    Pencil,
    Ban,
    Power,
    Paperclip,
    Download,
    CalendarDays,
    UserRound,
} from "lucide-react";

import "./AdminAnnouncements.css";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

const API = "/api/admin/announcements";

/**
 * Read the current Admin Announcement permissions from the Blade mount element.
 */
function getPermissions() {
    const root = document.querySelector(
        '[data-react-app="admin-announcements"]',
    );

    return {
        canView: root?.dataset.canView === "true",
        canCreate: root?.dataset.canCreate === "true",
        canUpdate: root?.dataset.canUpdate === "true",
        canDelete: root?.dataset.canDelete === "true",
        canPublish: root?.dataset.canPublish === "true",
    };
}

/**
 * Return the CSRF token required for Laravel state-changing requests.
 */
function getCsrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
}

/**
 * Formats announcement dates for the admin interface.
 */
function formatDate(value) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "—";

    return date.toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

/**
 * Formats announcement dates with time for the detail view.
 */
function formatDateTime(value) {
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
}

/**
 * Converts a server date into a datetime-local input value.
 */
function toDateTimeLocal(value) {
    if (!value) return "";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "";

    const offset = date.getTimezoneOffset() * 60000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

/**
 * Converts announcement status values into readable labels.
 */
function getStatusLabel(status) {
    const labels = {
        published: "Published",
        draft: "Draft",
        disabled: "Disabled",
        expired: "Expired",
    };

    return labels[status] || status || "Unknown";
}

/**
 * Determine whether a published announcement has passed its expiry date.
 */
function getDisplayStatus(item) {
    if (
        item.status === "published" &&
        item.expires_at &&
        new Date(item.expires_at).getTime() <= Date.now()
    ) {
        return "expired";
    }

    return item.status;
}

/**
 * Converts audience values into readable labels.
 */
function getAudienceLabel(audience) {
    const labels = {
        field_agents: "Field Agents",
        partners: "Partners",
        placement_partners: "Placement Partners",
        all_modules: "All Modules",
    };

    return labels[audience] || audience || "—";
}

/**
 * Converts recipient module types into readable labels.
 */
function getRecipientTypeLabel(type) {
    const labels = {
        field_agent: "Field Agent",
        partner: "Partner",
        placement_partner: "Placement Partner",
    };

    return labels[type] || type || "Recipient";
}

/**
 * Provides the shared premium SweetAlert configuration for Admin Announcements.
 */
const premiumAlert = Swal.mixin({
    customClass: {
        popup: "aa-swal-popup",
    },
    buttonsStyling: true,
    showClass: {
        popup: "swal2-show",
    },
    hideClass: {
        popup: "swal2-hide",
    },
});

/**
 * Displays the Admin Announcement Management listing.
 */
export default function AdminAnnouncements() {
    const permissions = getPermissions();

    const [announcements, setAnnouncements] = useState([]);
    const [stats, setStats] = useState({
        total: 0,
        published: 0,
        draft: 0,
        disabled: 0,
        expired: 0,
    });

    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [priority, setPriority] = useState("all");
    const [audience, setAudience] = useState("all");

    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);

    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState("");

    const [showCreateModal, setShowCreateModal] = useState(false);
    const [formMode, setFormMode] = useState("create");
    const [editingId, setEditingId] = useState(null);
    const [loadingEdit, setLoadingEdit] = useState(false);

    const [viewAnnouncement, setViewAnnouncement] = useState(null);
    const [loadingView, setLoadingView] = useState(false);

    const [processingAction, setProcessingAction] = useState(null);

    const [createForm, setCreateForm] = useState({
        title: "",
        message: "",
        priority: "normal",
        audience_type: "field_agents",
        target_type: "all",
        expires_at: "",
        attachment: null,
        remove_attachment: false,
    });

    const [recipientOptions, setRecipientOptions] = useState({
        field_agent: [],
        partner: [],
        placement_partner: [],
    });

    const [selectedRecipients, setSelectedRecipients] = useState([]);
    const [recipientLoading, setRecipientLoading] = useState(false);
    const [savingAnnouncement, setSavingAnnouncement] = useState(false);
    const [formError, setFormError] = useState("");
    const [recipientSearch, setRecipientSearch] = useState("");
    const [editingAttachmentName, setEditingAttachmentName] = useState("");

    /**
     * Fetch announcements using the selected filters and pagination.
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
                    search,
                    status,
                    priority,
                    audience_type: audience,
                });

                const response = await fetch(`${API}?${params.toString()}`, {
                    method: "GET",
                    headers: {
                        Accept: "application/json",
                    },
                    credentials: "same-origin",
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || "Unable to load announcements.",
                    );
                }

                const pagination = result.announcements || {};

                setAnnouncements(pagination.data || []);
                setStats({
                    total: result.stats?.total || 0,
                    published: result.stats?.published || 0,
                    draft: result.stats?.draft || 0,
                    disabled: result.stats?.disabled || 0,
                    expired: result.stats?.expired || 0,
                });

                setPage(pagination.current_page || 1);
                setLastPage(pagination.last_page || 1);
                setTotal(pagination.total || 0);
            } catch (err) {
                setError(err.message || "Something went wrong.");
                setAnnouncements([]);
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [page, search, status, priority, audience],
    );

    /**
     * Reloads the announcement listing when filters or page change.
     */
    useEffect(() => {
        loadAnnouncements();
    }, [loadAnnouncements]);

    /**
     * Changes the active filter and resets pagination.
     */
    const updateFilter = (setter, value) => {
        setter(value);
        setPage(1);
    };

    /**
     * Returns a formatted priority label.
     */
    const getPriorityLabel = (value) => {
        const labels = {
            normal: "Normal",
            important: "Important",
            urgent: "Urgent",
        };

        return labels[value] || value || "—";
    };

    /**
     * Loads available recipients for the selected announcement audience.
     */
    const loadRecipientOptions = useCallback(
        async (audienceType, searchValue = "") => {
            try {
                setRecipientLoading(true);

                const params = new URLSearchParams({
                    audience_type: audienceType,
                    search: searchValue,
                });

                const response = await fetch(
                    `${API}/recipients?${params.toString()}`,
                    {
                        headers: {
                            Accept: "application/json",
                        },
                        credentials: "same-origin",
                    },
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || "Unable to load recipients.",
                    );
                }

                setRecipientOptions({
                    field_agent: result.recipients?.field_agent || [],
                    partner: result.recipients?.partner || [],
                    placement_partner:
                        result.recipients?.placement_partner || [],
                });
            } catch (err) {
                setFormError(err.message || "Unable to load recipients.");
            } finally {
                setRecipientLoading(false);
            }
        },
        [],
    );

    /**
     * Resets the announcement creation and editing form.
     */
    const resetCreateForm = () => {
        setCreateForm({
            title: "",
            message: "",
            priority: "normal",
            audience_type: "field_agents",
            target_type: "all",
            expires_at: "",
            attachment: null,
            remove_attachment: false,
        });

        setSelectedRecipients([]);
        setRecipientOptions({
            field_agent: [],
            partner: [],
            placement_partner: [],
        });

        setRecipientSearch("");
        setFormError("");
        setEditingId(null);
        setFormMode("create");
        setLoadingEdit(false);
        setEditingAttachmentName("");
    };

    /**
     * Opens the create modal and loads the default audience recipients.
     */
    const openCreateModal = () => {
        resetCreateForm();
        setFormMode("create");
        setShowCreateModal(true);
        loadRecipientOptions("field_agents");
    };

    /**
     * Updates an individual announcement form field.
     */
    const updateCreateForm = (field, value) => {
        setCreateForm((current) => ({
            ...current,
            [field]: value,
        }));
    };

    /**
     * Changes the audience and reloads its available recipients.
     */
    const handleAudienceChange = (audienceType) => {
        updateCreateForm("audience_type", audienceType);
        setSelectedRecipients([]);
        setFormError("");
        loadRecipientOptions(audienceType, recipientSearch);
    };

    /**
     * Adds or removes a recipient from the selected recipient list.
     */
    const toggleRecipient = (type, id) => {
        setSelectedRecipients((current) => {
            const exists = current.some(
                (recipient) =>
                    recipient.type === type &&
                    Number(recipient.id) === Number(id),
            );

            if (exists) {
                return current.filter(
                    (recipient) =>
                        !(
                            recipient.type === type &&
                            Number(recipient.id) === Number(id)
                        ),
                );
            }

            return [...current, { type, id: Number(id) }];
        });
    };

    /**
     * Fetches complete announcement details and opens the View modal.
     */
    const openViewModal = async (id) => {
        try {
            setLoadingView(true);
            setViewAnnouncement(null);

            const response = await fetch(`${API}/${id}`, {
                headers: {
                    Accept: "application/json",
                },
                credentials: "same-origin",
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Unable to load announcement details.",
                );
            }

            setViewAnnouncement(result.announcement);
        } catch (err) {
            setError(err.message || "Unable to load announcement details.");
        } finally {
            setLoadingView(false);
        }
    };

    /**
     * Closes the View modal and clears its selected announcement.
     */
    const closeViewModal = () => {
        setViewAnnouncement(null);
        setLoadingView(false);
    };

    /**
     * Fetches announcement details and prepares the Edit modal.
     */
    const openEditModal = async (id) => {
        try {
            setFormError("");
            setEditingId(id);
            setFormMode("edit");
            setShowCreateModal(true);
            setLoadingEdit(true);

            setCreateForm({
                title: "",
                message: "",
                priority: "normal",
                audience_type: "field_agents",
                target_type: "all",
                expires_at: "",
                attachment: null,
                remove_attachment: false,
            });

            setSelectedRecipients([]);
            setRecipientSearch("");

            const response = await fetch(`${API}/${id}`, {
                headers: {
                    Accept: "application/json",
                },
                credentials: "same-origin",
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message ||
                        "Unable to load announcement for editing.",
                );
            }

            const announcement = result.announcement;
            setEditingAttachmentName(announcement.attachment_name || "");

            setCreateForm({
                title: announcement.title || "",
                message: announcement.message || "",
                priority: announcement.priority || "normal",
                audience_type: announcement.audience_type || "field_agents",
                target_type: announcement.target_type || "all",
                expires_at: toDateTimeLocal(announcement.expires_at),
                attachment: null,
                remove_attachment: false,
            });

            setSelectedRecipients(
                (announcement.recipients || []).map((recipient) => ({
                    type: recipient.type,
                    id: Number(recipient.id),
                })),
            );

            await loadRecipientOptions(
                announcement.audience_type || "field_agents",
            );
        } catch (err) {
            setFormError(err.message || "Unable to open announcement.");
        } finally {
            setLoadingEdit(false);
        }
    };

    /**
     * Closes the create or edit modal and resets its form state.
     */
    const closeFormModal = () => {
        if (savingAnnouncement) return;

        setShowCreateModal(false);
        resetCreateForm();
    };

    /**
     * Builds the multipart request body for creating or updating an announcement.
     */
    const buildAnnouncementFormData = (action = null) => {
        const formData = new FormData();

        formData.append("title", createForm.title.trim());
        formData.append("message", createForm.message.trim());
        formData.append("priority", createForm.priority);
        formData.append("audience_type", createForm.audience_type);
        formData.append("target_type", createForm.target_type);

        if (action) {
            formData.append("action", action);
        }

        if (createForm.expires_at) {
            formData.append("expires_at", createForm.expires_at);
        }

        if (createForm.attachment) {
            formData.append("attachment", createForm.attachment);
        }

        if (createForm.remove_attachment) {
            formData.append("remove_attachment", "1");
        }

        if (createForm.target_type === "selected") {
            selectedRecipients.forEach((recipient, index) => {
                formData.append(`recipients[${index}][type]`, recipient.type);

                formData.append(
                    `recipients[${index}][id]`,
                    String(recipient.id),
                );
            });
        }

        return formData;
    };

    /**
     * Submits a new announcement as a draft or publishes it immediately.
     */
    const handleCreateAnnouncement = async (action) => {
        try {
            setFormError("");

            if (!createForm.title.trim()) {
                setFormError("Please enter an announcement title.");
                return;
            }

            if (!createForm.message.trim()) {
                setFormError("Please enter the announcement message.");
                return;
            }

            if (
                createForm.target_type === "selected" &&
                selectedRecipients.length === 0
            ) {
                setFormError("Please select at least one recipient.");
                return;
            }

            if (
                createForm.attachment &&
                createForm.attachment.size > 10 * 1024 * 1024
            ) {
                setFormError("Attachment size must not exceed 10 MB.");
                return;
            }

            setSavingAnnouncement(true);

            const headers = {
                Accept: "application/json",
            };

            const csrfToken = getCsrfToken();

            if (csrfToken) {
                headers["X-CSRF-TOKEN"] = csrfToken;
            }

            const response = await fetch(API, {
                method: "POST",
                headers,
                body: buildAnnouncementFormData(action),
                credentials: "same-origin",
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                const validationErrors = result.errors
                    ? Object.values(result.errors).flat().join(" ")
                    : "";

                throw new Error(
                    validationErrors ||
                        result.message ||
                        "Unable to create announcement.",
                );
            }

            setShowCreateModal(false);
            resetCreateForm();

            if (page === 1) {
                await loadAnnouncements(true);
            } else {
                setPage(1);
            }
        } catch (err) {
            setFormError(err.message || "Something went wrong.");
        } finally {
            setSavingAnnouncement(false);
        }
    };

    /**
     * Saves changes to an existing announcement using Laravel method spoofing.
     */
    const handleUpdateAnnouncement = async () => {
        try {
            setFormError("");

            if (!editingId) {
                throw new Error("Announcement ID is missing.");
            }

            if (!createForm.title.trim()) {
                setFormError("Please enter an announcement title.");
                return;
            }

            if (!createForm.message.trim()) {
                setFormError("Please enter the announcement message.");
                return;
            }

            if (
                createForm.target_type === "selected" &&
                selectedRecipients.length === 0
            ) {
                setFormError("Please select at least one recipient.");
                return;
            }

            if (
                createForm.attachment &&
                createForm.attachment.size > 10 * 1024 * 1024
            ) {
                setFormError("Attachment size must not exceed 10 MB.");
                return;
            }

            setSavingAnnouncement(true);

            const formData = buildAnnouncementFormData();
            formData.append("_method", "PUT");

            const headers = {
                Accept: "application/json",
            };

            const csrfToken = getCsrfToken();

            if (csrfToken) {
                headers["X-CSRF-TOKEN"] = csrfToken;
            }

            const response = await fetch(`${API}/${editingId}`, {
                method: "POST",
                headers,
                body: formData,
                credentials: "same-origin",
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                const validationErrors = result.errors
                    ? Object.values(result.errors).flat().join(" ")
                    : "";

                throw new Error(
                    validationErrors ||
                        result.message ||
                        "Unable to update announcement.",
                );
            }

            setShowCreateModal(false);
            resetCreateForm();

            await loadAnnouncements(true);
        } catch (err) {
            setFormError(err.message || "Unable to update announcement.");
        } finally {
            setSavingAnnouncement(false);
        }
    };

    /**
     * Handles announcement status changes with premium confirmation and feedback.
     */
    const handleStatusAction = async (id, action) => {
        const isDisable = action === "disable";

        const result = await premiumAlert.fire({
            title: isDisable
                ? "Disable Announcement?"
                : "Activate Announcement?",
            html: isDisable
                ? "This announcement will be <b>temporarily deactivated</b> and will no longer be available to recipients."
                : "This announcement will be <b>activated again</b> and made available to recipients.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: isDisable ? "Yes, Disable" : "Yes, Activate",
            cancelButtonText: "Keep it",
            confirmButtonColor: isDisable ? "#e04444" : "#2865dc",
            reverseButtons: true,
            focusCancel: true,
        });

        if (!result.isConfirmed) return;

        try {
            const response = await fetch(`${API}/${id}/${action}`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.message || "Unable to update announcement.",
                );
            }

            await premiumAlert.fire({
                title: isDisable
                    ? "Announcement Disabled!"
                    : "Announcement Activated!",
                text: data.message,
                icon: "success",
                confirmButtonText: "Perfect!",
                confirmButtonColor: "#2865dc",
                timer: 2000,
                timerProgressBar: true,
            });

            await loadAnnouncements();
        } catch (error) {
            premiumAlert.fire({
                title: "Something Went Wrong",
                text: error.message || "Please try again.",
                icon: "error",
                confirmButtonText: "Understood",
                confirmButtonColor: "#dc3545",
            });
        }
    };

    /**
     * Returns the recipient groups that belong to the selected audience.
     */
    const getVisibleRecipientGroups = () => {
        const groups = [
            {
                type: "field_agent",
                label: "Field Agents",
                items: recipientOptions.field_agent,
            },
            {
                type: "partner",
                label: "Partners",
                items: recipientOptions.partner,
            },
            {
                type: "placement_partner",
                label: "Placement Partners",
                items: recipientOptions.placement_partner,
            },
        ];

        const allowedTypes = {
            field_agents: ["field_agent"],
            partners: ["partner"],
            placement_partners: ["placement_partner"],
            all_modules: ["field_agent", "partner", "placement_partner"],
        };

        return groups.filter((group) =>
            (allowedTypes[createForm.audience_type] || []).includes(group.type),
        );
    };

    return (
        <div className="aa-page">
            <div className="aa-header">
                <div className="aa-heading">
                    <div className="aa-heading__icon">
                        <Megaphone size={23} />
                    </div>

                    <div>
                        <p className="aa-eyebrow">
                            ADMINISTRATION / COMMUNICATION
                        </p>
                        <h1>Announcements</h1>
                        <p>
                            Create, manage and publish announcements across
                            YouthHub modules.
                        </p>
                    </div>
                </div>

                <div className="aa-header__actions">
                    <a href="/admin/dashboard" className="aa-back-btn">
                        <ChevronLeft size={16} />
                        Back to Dashboard
                    </a>

                    {permissions.canCreate && (
                        <button
                            type="button"
                            className="aa-add-btn"
                            onClick={openCreateModal}
                        >
                            <Megaphone size={16} />
                            Add Announcement
                        </button>
                    )}
                </div>
            </div>

            <section className="aa-stats">
                <div className="aa-stat-card">
                    <div className="aa-stat-card__icon aa-icon-blue">
                        <FileText size={19} />
                    </div>
                    <div>
                        <span>Total Announcements</span>
                        <strong>
                            {Number(stats.total || 0).toLocaleString("en-IN")}
                        </strong>
                    </div>
                </div>

                <div className="aa-stat-card">
                    <div className="aa-stat-card__icon aa-icon-green">
                        <CheckCircle2 size={19} />
                    </div>
                    <div>
                        <span>Published</span>
                        <strong>
                            {Number(stats.published || 0).toLocaleString(
                                "en-IN",
                            )}
                        </strong>
                    </div>
                </div>

                <div className="aa-stat-card">
                    <div className="aa-stat-card__icon aa-icon-orange">
                        <Clock3 size={19} />
                    </div>
                    <div>
                        <span>Drafts</span>
                        <strong>
                            {Number(stats.draft || 0).toLocaleString("en-IN")}
                        </strong>
                    </div>
                </div>

                <div className="aa-stat-card">
                    <div className="aa-stat-card__icon aa-icon-red">
                        <AlertCircle size={19} />
                    </div>
                    <div>
                        <span>Expired</span>
                        <strong>
                            {Number(stats.expired || 0).toLocaleString("en-IN")}
                        </strong>
                    </div>
                </div>

                <div className="aa-stat-card">
                    <div className="aa-stat-card__icon aa-icon-orange">
                        <Ban size={19} />
                    </div>
                    <div>
                        <span>Disabled</span>
                        <strong>
                            {Number(stats.disabled || 0).toLocaleString(
                                "en-IN",
                            )}
                        </strong>
                    </div>
                </div>
            </section>

            <section className="aa-panel">
                <div className="aa-toolbar">
                    <div className="aa-search">
                        <Search size={17} />
                        <input
                            type="search"
                            placeholder="Search announcements..."
                            value={search}
                            onChange={(event) =>
                                updateFilter(setSearch, event.target.value)
                            }
                        />
                    </div>

                    <div className="aa-filters">
                        <select
                            value={status}
                            onChange={(event) =>
                                updateFilter(setStatus, event.target.value)
                            }
                            aria-label="Filter by status"
                        >
                            <option value="all">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="disabled">Disabled</option>
                            <option value="expired">Expired</option>
                        </select>

                        <select
                            value={priority}
                            onChange={(event) =>
                                updateFilter(setPriority, event.target.value)
                            }
                            aria-label="Filter by priority"
                        >
                            <option value="all">All Priorities</option>
                            <option value="normal">Normal</option>
                            <option value="important">Important</option>
                            <option value="urgent">Urgent</option>
                        </select>

                        <select
                            value={audience}
                            onChange={(event) =>
                                updateFilter(setAudience, event.target.value)
                            }
                            aria-label="Filter by audience"
                        >
                            <option value="all">All Audiences</option>
                            <option value="field_agents">Field Agents</option>
                            <option value="partners">Partners</option>
                            <option value="placement_partners">
                                Placement Partners
                            </option>
                            <option value="all_modules">All Modules</option>
                        </select>

                        <button
                            type="button"
                            className="aa-refresh-btn"
                            onClick={() => loadAnnouncements(true)}
                            disabled={refreshing || loading}
                            title="Refresh announcements"
                        >
                            <RefreshCw
                                size={16}
                                className={refreshing ? "aa-spin" : ""}
                            />
                        </button>
                    </div>
                </div>

                {error && (
                    <div className="aa-error" role="alert">
                        <AlertCircle size={17} />
                        <span>{error}</span>
                        <button
                            type="button"
                            onClick={() => loadAnnouncements(true)}
                        >
                            Retry
                        </button>
                        <button
                            type="button"
                            onClick={() => setError("")}
                            aria-label="Dismiss error"
                        >
                            <X size={15} />
                        </button>
                    </div>
                )}

                <div className="aa-table-wrap">
                    <table className="aa-table">
                        <thead>
                            <tr>
                                <th>Announcement</th>
                                <th>Audience</th>
                                <th>Priority</th>
                                <th>Recipients</th>
                                <th>Status</th>
                                <th>Published / Created</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                Array.from({ length: 5 }).map((_, index) => (
                                    <tr key={index} className="aa-loading-row">
                                        <td colSpan="8">
                                            <span />
                                        </td>
                                    </tr>
                                ))
                            ) : announcements.length > 0 ? (
                                announcements.map((item) => {
                                    const displayStatus =
                                        getDisplayStatus(item);

                                    return (
                                        <tr key={item.id}>
                                            <td>
                                                <div className="aa-announcement-title">
                                                    <strong>
                                                        {item.title}
                                                    </strong>
                                                    <small>
                                                        {item.message ||
                                                            "No message"}
                                                    </small>
                                                </div>
                                            </td>

                                            <td>
                                                <span className="aa-audience">
                                                    <UsersRound size={14} />
                                                    {getAudienceLabel(
                                                        item.audience_type,
                                                    )}
                                                </span>
                                            </td>

                                            <td>
                                                <span
                                                    className={`aa-priority aa-priority--${item.priority}`}
                                                >
                                                    {getPriorityLabel(
                                                        item.priority,
                                                    )}
                                                </span>
                                            </td>

                                            <td>
                                                {Number(
                                                    item.recipients_count ??
                                                        item.recipients_count_count ??
                                                        0,
                                                ).toLocaleString("en-IN")}
                                            </td>

                                            <td>
                                                <span
                                                    className={`aa-status aa-status--${displayStatus}`}
                                                >
                                                    {getStatusLabel(
                                                        displayStatus,
                                                    )}
                                                </span>
                                            </td>

                                            <td>
                                                {formatDate(
                                                    item.published_at ||
                                                        item.created_at,
                                                )}
                                            </td>

                                            <td>
                                                {formatDate(item.expires_at)}
                                            </td>

                                            <td>
                                                <div className="aa-actions">
                                                    {permissions.canView && (
                                                        <button
                                                            type="button"
                                                            className="aa-action-btn aa-action-view"
                                                            onClick={() =>
                                                                openViewModal(
                                                                    item.id,
                                                                )
                                                            }
                                                            title="View announcement"
                                                        >
                                                            <Eye size={15} />
                                                        </button>
                                                    )}

                                                    {permissions.canUpdate &&
                                                        item.status !==
                                                            "deleted" && (
                                                            <button
                                                                type="button"
                                                                className="aa-action-btn aa-action-edit"
                                                                onClick={() =>
                                                                    openEditModal(
                                                                        item.id,
                                                                    )
                                                                }
                                                                title="Edit announcement"
                                                            >
                                                                <Pencil
                                                                    size={15}
                                                                />
                                                            </button>
                                                        )}

                                                    {permissions.canPublish &&
                                                        item.status ===
                                                            "published" &&
                                                        displayStatus ===
                                                            "published" && (
                                                            <button
                                                                type="button"
                                                                className="aa-action-btn aa-action-disable"
                                                                onClick={() =>
                                                                    handleStatusAction(
                                                                        item.id,
                                                                        "disable",
                                                                    )
                                                                }
                                                                disabled={
                                                                    processingAction ===
                                                                    `disable-${item.id}`
                                                                }
                                                                title="Disable announcement"
                                                            >
                                                                <Ban
                                                                    size={15}
                                                                />
                                                            </button>
                                                        )}

                                                    {permissions.canPublish &&
                                                        item.status ===
                                                            "disabled" && (
                                                            <button
                                                                type="button"
                                                                className="aa-action-btn aa-action-enable"
                                                                onClick={() =>
                                                                    handleStatusAction(
                                                                        item.id,
                                                                        "enable",
                                                                    )
                                                                }
                                                                disabled={
                                                                    processingAction ===
                                                                    `enable-${item.id}`
                                                                }
                                                                title="Enable announcement"
                                                            >
                                                                <Power
                                                                    size={15}
                                                                />
                                                            </button>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td colSpan="8">
                                        <div className="aa-empty">
                                            <div className="aa-empty__icon">
                                                <Megaphone size={25} />
                                            </div>
                                            <strong>
                                                No announcements found
                                            </strong>
                                            <span>
                                                Announcements matching your
                                                filters will appear here.
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="aa-pagination">
                    <span>
                        Showing{" "}
                        <strong>
                            {total === 0 ? 0 : (page - 1) * 10 + 1}
                            {" - "}
                            {Math.min(page * 10, total)}
                        </strong>{" "}
                        of <strong>{total}</strong> announcements
                    </span>

                    <div className="aa-pagination__controls">
                        <button
                            type="button"
                            disabled={page <= 1 || loading}
                            onClick={() => setPage((current) => current - 1)}
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>

                        <span>
                            Page <strong>{page}</strong> of{" "}
                            <strong>{lastPage}</strong>
                        </span>

                        <button
                            type="button"
                            disabled={page >= lastPage || loading}
                            onClick={() => setPage((current) => current + 1)}
                            aria-label="Next page"
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </div>
            </section>

            {/* Create and Edit Announcement Modal */}
            {showCreateModal && (
                <div
                    className="aa-modal-overlay"
                    onClick={(event) => {
                        if (
                            event.target === event.currentTarget &&
                            !savingAnnouncement
                        ) {
                            closeFormModal();
                        }
                    }}
                >
                    <div className="aa-modal aa-create-modal">
                        <div className="aa-modal__header">
                            <div>
                                <span className="aa-eyebrow">
                                    ADMINISTRATION
                                </span>
                                <h2>
                                    {formMode === "edit"
                                        ? "Edit Announcement"
                                        : "Create Announcement"}
                                </h2>
                                <p>
                                    {formMode === "edit"
                                        ? "Update announcement details and recipient selection."
                                        : "Compose and distribute a new YouthHub announcement."}
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={closeFormModal}
                                disabled={savingAnnouncement}
                                aria-label="Close modal"
                            >
                                <X size={19} />
                            </button>
                        </div>

                        <div className="aa-modal__body">
                            {loadingEdit ? (
                                <div className="aa-modal-loading">
                                    <RefreshCw size={22} className="aa-spin" />
                                    <span>Loading announcement details...</span>
                                </div>
                            ) : (
                                <>
                                    {formError && (
                                        <div
                                            className="aa-form-error"
                                            role="alert"
                                        >
                                            <AlertCircle size={17} />
                                            <span>{formError}</span>
                                        </div>
                                    )}

                                    <div className="aa-form-grid">
                                        <div className="aa-form-group aa-form-full">
                                            <label>
                                                Announcement Title{" "}
                                                <span>*</span>
                                            </label>
                                            <input
                                                type="text"
                                                value={createForm.title}
                                                onChange={(event) =>
                                                    updateCreateForm(
                                                        "title",
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Enter announcement title"
                                                maxLength={200}
                                            />
                                        </div>

                                        <div className="aa-form-group aa-form-full">
                                            <label>
                                                Announcement Message{" "}
                                                <span>*</span>
                                            </label>
                                            <textarea
                                                rows={5}
                                                value={createForm.message}
                                                onChange={(event) =>
                                                    updateCreateForm(
                                                        "message",
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Write your announcement message..."
                                            />
                                        </div>

                                        <div className="aa-form-group">
                                            <label>
                                                Audience <span>*</span>
                                            </label>
                                            <select
                                                value={createForm.audience_type}
                                                onChange={(event) =>
                                                    handleAudienceChange(
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                <option value="field_agents">
                                                    Field Agents
                                                </option>
                                                <option value="partners">
                                                    Partners
                                                </option>
                                                <option value="placement_partners">
                                                    Placement Partners
                                                </option>
                                                <option value="all_modules">
                                                    All Modules
                                                </option>
                                            </select>
                                        </div>

                                        <div className="aa-form-group">
                                            <label>
                                                Recipient Target <span>*</span>
                                            </label>
                                            <select
                                                value={createForm.target_type}
                                                onChange={(event) => {
                                                    updateCreateForm(
                                                        "target_type",
                                                        event.target.value,
                                                    );

                                                    if (
                                                        event.target.value ===
                                                        "all"
                                                    ) {
                                                        setSelectedRecipients(
                                                            [],
                                                        );
                                                    }
                                                }}
                                            >
                                                <option value="all">
                                                    All Recipients
                                                </option>
                                                <option value="selected">
                                                    Selected Recipients
                                                </option>
                                            </select>
                                        </div>

                                        <div className="aa-form-group">
                                            <label>Priority</label>
                                            <select
                                                value={createForm.priority}
                                                onChange={(event) =>
                                                    updateCreateForm(
                                                        "priority",
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                <option value="normal">
                                                    Normal
                                                </option>
                                                <option value="important">
                                                    Important
                                                </option>
                                                <option value="urgent">
                                                    Urgent
                                                </option>
                                            </select>
                                        </div>

                                        <div className="aa-form-group">
                                            <label>
                                                Expiry Date (Optional)
                                            </label>
                                            <input
                                                type="datetime-local"
                                                value={createForm.expires_at}
                                                onChange={(event) =>
                                                    updateCreateForm(
                                                        "expires_at",
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </div>

                                        {createForm.target_type ===
                                            "selected" && (
                                            <div className="aa-form-group aa-form-full">
                                                <div className="aa-recipient-heading">
                                                    <label>
                                                        Select Recipients{" "}
                                                        <span>*</span>
                                                    </label>
                                                    <strong>
                                                        {
                                                            selectedRecipients.length
                                                        }{" "}
                                                        selected
                                                    </strong>
                                                </div>

                                                <div className="aa-recipient-search">
                                                    <Search size={16} />
                                                    <input
                                                        type="search"
                                                        placeholder="Search recipients by name or email..."
                                                        value={recipientSearch}
                                                        onChange={(event) => {
                                                            const value =
                                                                event.target
                                                                    .value;

                                                            setRecipientSearch(
                                                                value,
                                                            );

                                                            loadRecipientOptions(
                                                                createForm.audience_type,
                                                                value,
                                                            );
                                                        }}
                                                    />
                                                </div>

                                                <div className="aa-recipient-list">
                                                    {recipientLoading ? (
                                                        <div className="aa-recipient-loading">
                                                            Loading
                                                            recipients...
                                                        </div>
                                                    ) : (
                                                        getVisibleRecipientGroups()
                                                            .filter(
                                                                (group) =>
                                                                    group.items
                                                                        .length >
                                                                    0,
                                                            )
                                                            .map((group) => (
                                                                <div
                                                                    className="aa-recipient-group"
                                                                    key={
                                                                        group.type
                                                                    }
                                                                >
                                                                    <div className="aa-recipient-group__title">
                                                                        {
                                                                            group.label
                                                                        }
                                                                        <span>
                                                                            {
                                                                                group
                                                                                    .items
                                                                                    .length
                                                                            }
                                                                        </span>
                                                                    </div>

                                                                    {group.items.map(
                                                                        (
                                                                            recipient,
                                                                        ) => (
                                                                            <label
                                                                                className="aa-recipient-item"
                                                                                key={`${group.type}-${recipient.id}`}
                                                                            >
                                                                                <input
                                                                                    type="checkbox"
                                                                                    checked={selectedRecipients.some(
                                                                                        (
                                                                                            selected,
                                                                                        ) =>
                                                                                            selected.type ===
                                                                                                group.type &&
                                                                                            Number(
                                                                                                selected.id,
                                                                                            ) ===
                                                                                                Number(
                                                                                                    recipient.id,
                                                                                                ),
                                                                                    )}
                                                                                    onChange={() =>
                                                                                        toggleRecipient(
                                                                                            group.type,
                                                                                            recipient.id,
                                                                                        )
                                                                                    }
                                                                                />

                                                                                <span className="aa-recipient-item__details">
                                                                                    <strong>
                                                                                        {
                                                                                            recipient.name
                                                                                        }
                                                                                    </strong>
                                                                                    <small>
                                                                                        {recipient.email ||
                                                                                            recipient.code ||
                                                                                            `ID: ${recipient.id}`}
                                                                                    </small>
                                                                                </span>
                                                                            </label>
                                                                        ),
                                                                    )}
                                                                </div>
                                                            ))
                                                    )}

                                                    {!recipientLoading &&
                                                        getVisibleRecipientGroups().every(
                                                            (group) =>
                                                                !group.items
                                                                    .length,
                                                        ) && (
                                                            <div className="aa-recipient-loading">
                                                                No recipients
                                                                found.
                                                            </div>
                                                        )}
                                                </div>

                                                <small className="aa-field-hint">
                                                    Select the users who should
                                                    receive this announcement.
                                                    Existing selections are
                                                    retained unless you change
                                                    them.
                                                </small>
                                            </div>
                                        )}

                                        <div className="aa-form-group aa-form-full">
                                            <label>Attachment (Optional)</label>
                                            {formMode === "edit" &&
                                                !createForm.remove_attachment &&
                                                editingAttachmentName && (
                                                    <div className="aa-existing-attachment">
                                                        <Paperclip size={16} />
                                                        <span>
                                                            {
                                                                editingAttachmentName
                                                            }
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                updateCreateForm(
                                                                    "remove_attachment",
                                                                    true,
                                                                )
                                                            }
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>
                                                )}

                                            {createForm.remove_attachment && (
                                                <div className="aa-removed-attachment">
                                                    Existing attachment will be
                                                    removed when you save.
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            updateCreateForm(
                                                                "remove_attachment",
                                                                false,
                                                            )
                                                        }
                                                    >
                                                        Undo
                                                    </button>
                                                </div>
                                            )}

                                            <label className="aa-upload-box">
                                                <Upload size={20} />
                                                <span>
                                                    <strong>
                                                        {createForm.attachment
                                                            ? createForm
                                                                  .attachment
                                                                  .name
                                                            : "Click to upload an attachment"}
                                                    </strong>
                                                    <small>
                                                        PDF, JPG, JPEG or PNG ·
                                                        Maximum 10 MB
                                                    </small>
                                                </span>

                                                <input
                                                    type="file"
                                                    accept=".pdf,.jpg,.jpeg,.png"
                                                    onChange={(event) =>
                                                        updateCreateForm(
                                                            "attachment",
                                                            event.target
                                                                .files?.[0] ||
                                                                null,
                                                        )
                                                    }
                                                />
                                            </label>

                                            {createForm.attachment && (
                                                <button
                                                    type="button"
                                                    className="aa-remove-file"
                                                    onClick={() =>
                                                        updateCreateForm(
                                                            "attachment",
                                                            null,
                                                        )
                                                    }
                                                >
                                                    <X size={14} />
                                                    Remove selected file
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>

                        {!loadingEdit && (
                            <div className="aa-modal__footer">
                                <button
                                    type="button"
                                    className="aa-cancel-btn"
                                    onClick={closeFormModal}
                                    disabled={savingAnnouncement}
                                >
                                    Cancel
                                </button>

                                {formMode === "create" ? (
                                    <>
                                        <button
                                            type="button"
                                            className="aa-draft-btn"
                                            onClick={() =>
                                                handleCreateAnnouncement(
                                                    "draft",
                                                )
                                            }
                                            disabled={savingAnnouncement}
                                        >
                                            <Save size={16} />
                                            Save Draft
                                        </button>

                                        <button
                                            type="button"
                                            className="aa-publish-btn"
                                            onClick={() =>
                                                handleCreateAnnouncement(
                                                    "publish",
                                                )
                                            }
                                            disabled={savingAnnouncement}
                                        >
                                            <Send size={16} />
                                            {savingAnnouncement
                                                ? "Saving..."
                                                : "Publish Now"}
                                        </button>
                                    </>
                                ) : (
                                    <button
                                        type="button"
                                        className="aa-publish-btn"
                                        onClick={handleUpdateAnnouncement}
                                        disabled={savingAnnouncement}
                                    >
                                        <Save size={16} />
                                        {savingAnnouncement
                                            ? "Updating..."
                                            : "Save Changes"}
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* View Announcement Details Modal */}
            {(loadingView || viewAnnouncement) && (
                <div
                    className="aa-modal-overlay"
                    onClick={(event) => {
                        if (event.target === event.currentTarget) {
                            closeViewModal();
                        }
                    }}
                >
                    <div className="aa-modal aa-view-modal">
                        <div className="aa-modal__header">
                            <div>
                                <span className="aa-eyebrow">
                                    ANNOUNCEMENT DETAILS
                                </span>
                                <h2>View Announcement</h2>
                                <p>
                                    Complete announcement information and
                                    recipient activity.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={closeViewModal}
                                aria-label="Close modal"
                            >
                                <X size={19} />
                            </button>
                        </div>

                        <div className="aa-modal__body">
                            {loadingView ? (
                                <div className="aa-modal-loading">
                                    <RefreshCw size={22} className="aa-spin" />
                                    <span>Loading announcement details...</span>
                                </div>
                            ) : (
                                viewAnnouncement && (
                                    <div className="aa-view-content">
                                        <div className="aa-view-title-row">
                                            <div>
                                                <h3>
                                                    {viewAnnouncement.title}
                                                </h3>
                                                <p>
                                                    Announcement #
                                                    {viewAnnouncement.id}
                                                </p>
                                            </div>

                                            <span
                                                className={`aa-status aa-status--${getDisplayStatus(viewAnnouncement)}`}
                                            >
                                                {getStatusLabel(
                                                    getDisplayStatus(
                                                        viewAnnouncement,
                                                    ),
                                                )}
                                            </span>
                                        </div>

                                        <div className="aa-view-meta">
                                            <div>
                                                <span>Audience</span>
                                                <strong>
                                                    {getAudienceLabel(
                                                        viewAnnouncement.audience_type,
                                                    )}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Target</span>
                                                <strong>
                                                    {viewAnnouncement.target_type ===
                                                    "all"
                                                        ? "All Recipients"
                                                        : "Selected Recipients"}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Priority</span>
                                                <strong>
                                                    {getPriorityLabel(
                                                        viewAnnouncement.priority,
                                                    )}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Published At</span>
                                                <strong>
                                                    {formatDateTime(
                                                        viewAnnouncement.published_at,
                                                    )}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Created At</span>
                                                <strong>
                                                    {formatDateTime(
                                                        viewAnnouncement.created_at,
                                                    )}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Expires At</span>
                                                <strong>
                                                    {formatDateTime(
                                                        viewAnnouncement.expires_at,
                                                    )}
                                                </strong>
                                            </div>
                                        </div>

                                        <div className="aa-view-message">
                                            <h4>Announcement Message</h4>
                                            <p>{viewAnnouncement.message}</p>
                                        </div>

                                        {viewAnnouncement.attachment_name && (
                                            <div className="aa-view-attachment">
                                                <div>
                                                    <Paperclip size={17} />
                                                    <span>
                                                        {
                                                            viewAnnouncement.attachment_name
                                                        }
                                                    </span>
                                                </div>

                                                <a
                                                    href={`${API}/${viewAnnouncement.id}/attachment`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    <Download size={15} />
                                                    Download
                                                </a>
                                            </div>
                                        )}

                                        <div className="aa-view-recipient-summary">
                                            <div>
                                                <UsersRound size={18} />
                                                <span>
                                                    Total Recipients
                                                    <strong>
                                                        {Number(
                                                            viewAnnouncement.recipients_count ||
                                                                0,
                                                        ).toLocaleString(
                                                            "en-IN",
                                                        )}
                                                    </strong>
                                                </span>
                                            </div>

                                            <div>
                                                <CheckCircle2 size={18} />
                                                <span>
                                                    Read
                                                    <strong>
                                                        {Number(
                                                            viewAnnouncement.read_count ||
                                                                0,
                                                        ).toLocaleString(
                                                            "en-IN",
                                                        )}
                                                    </strong>
                                                </span>
                                            </div>

                                            <div>
                                                <Clock3 size={18} />
                                                <span>
                                                    Unread
                                                    <strong>
                                                        {Math.max(
                                                            0,
                                                            Number(
                                                                viewAnnouncement.recipients_count ||
                                                                    0,
                                                            ) -
                                                                Number(
                                                                    viewAnnouncement.read_count ||
                                                                        0,
                                                                ),
                                                        ).toLocaleString(
                                                            "en-IN",
                                                        )}
                                                    </strong>
                                                </span>
                                            </div>
                                        </div>

                                        <div className="aa-view-recipient-section">
                                            <div className="aa-view-section-heading">
                                                <div>
                                                    <h4>Recipient Activity</h4>
                                                    <p>
                                                        Users assigned to this
                                                        announcement and their
                                                        read status.
                                                    </p>
                                                </div>

                                                <span>
                                                    {
                                                        (
                                                            viewAnnouncement.recipients ||
                                                            []
                                                        ).length
                                                    }{" "}
                                                    records
                                                </span>
                                            </div>

                                            <div className="aa-view-recipient-table-wrap">
                                                <table className="aa-view-recipient-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Recipient</th>
                                                            <th>Module</th>
                                                            <th>Read Status</th>
                                                            <th>Read At</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        {(
                                                            viewAnnouncement.recipients ||
                                                            []
                                                        ).length > 0 ? (
                                                            viewAnnouncement.recipients.map(
                                                                (
                                                                    recipient,
                                                                    index,
                                                                ) => (
                                                                    <tr
                                                                        key={`${recipient.type}-${recipient.id}-${index}`}
                                                                    >
                                                                        <td>
                                                                            <div className="aa-view-recipient-name">
                                                                                <UserRound
                                                                                    size={
                                                                                        15
                                                                                    }
                                                                                />
                                                                                <span>
                                                                                    <strong>
                                                                                        {
                                                                                            recipient.name
                                                                                        }
                                                                                    </strong>
                                                                                    <small>
                                                                                        {recipient.email ||
                                                                                            `ID: ${recipient.id}`}
                                                                                    </small>
                                                                                </span>
                                                                            </div>
                                                                        </td>

                                                                        <td>
                                                                            {getRecipientTypeLabel(
                                                                                recipient.type,
                                                                            )}
                                                                        </td>

                                                                        <td>
                                                                            <span
                                                                                className={`aa-read-status ${recipient.is_read ? "aa-read-status--read" : "aa-read-status--unread"}`}
                                                                            >
                                                                                {recipient.is_read
                                                                                    ? "Read"
                                                                                    : "Unread"}
                                                                            </span>
                                                                        </td>

                                                                        <td>
                                                                            {formatDateTime(
                                                                                recipient.read_at,
                                                                            )}
                                                                        </td>
                                                                    </tr>
                                                                ),
                                                            )
                                                        ) : (
                                                            <tr>
                                                                <td
                                                                    colSpan="4"
                                                                    className="aa-view-no-recipients"
                                                                >
                                                                    No recipient
                                                                    records
                                                                    available.
                                                                </td>
                                                            </tr>
                                                        )}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                )
                            )}
                        </div>

                        <div className="aa-modal__footer">
                            <button
                                type="button"
                                className="aa-cancel-btn"
                                onClick={closeViewModal}
                            >
                                Close
                            </button>

                            {permissions.canUpdate && viewAnnouncement && (
                                <button
                                    type="button"
                                    className="aa-publish-btn"
                                    onClick={() => {
                                        const id = viewAnnouncement.id;
                                        closeViewModal();
                                        openEditModal(id);
                                    }}
                                >
                                    <Pencil size={15} />
                                    Edit Announcement
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
