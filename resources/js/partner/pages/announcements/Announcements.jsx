import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
    Megaphone,
    Plus,
    Search,
    RefreshCw,
    CheckCircle2,
    FilePenLine,
    Clock3,
    Users,
    Eye,
    Pencil,
    Trash2,
    Send,
    X,
    ChevronLeft,
    ChevronRight,
    Paperclip,
    Download,
    AlertCircle,
    LoaderCircle,
    Filter,
    CalendarDays,
    FileText,
    ShieldAlert,
    Check,
    UserRound,
} from "lucide-react";
import "./Announcements.css";

const API = "/api/partner/announcements";
const EMPTY_FORM = {
    title: "",
    message: "",
    priority: "normal",
    target_type: "all",
    field_agent_ids: [],
    expires_at: "",
    attachment: null,
};
const TABS = [
    { value: "all", label: "All" },
    { value: "published", label: "Published" },
    { value: "draft", label: "Drafts" },
    { value: "expired", label: "Expired" },
];

// Extract the best available error message from a Laravel API response.
async function readResponse(response) {
    const result = await response.json().catch(() => ({}));
    if (!response.ok || result.success === false) {
        const validation = result.errors
            ? Object.values(result.errors).flat().join(" ")
            : "";
        throw new Error(
            validation ||
                result.message ||
                `Request failed (${response.status}).`,
        );
    }
    return result;
}

// Format a timestamp for display in the partner's local timezone.
function formatDate(value) {
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

// Convert an ISO datetime to the local value expected by datetime-local inputs.
function toLocalInput(value) {
    if (!value) return "";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";
    const offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

// Resolve the visible status, including announcements whose expiry has passed.
function visibleStatus(item) {
    return item.status === "published" &&
        item.expires_at &&
        new Date(item.expires_at).getTime() <= Date.now()
        ? "expired"
        : item.status || "draft";
}

// Render a compact status or priority pill.
function Pill({ value, kind = "status" }) {
    return (
        <span className={`yp-an-pill yp-an-pill--${kind}-${value || "normal"}`}>
            <span className="yp-an-pill-dot" />
            {String(value || "normal").replace(/^./, (c) => c.toUpperCase())}
        </span>
    );
}

// Display placeholder rows while the announcements API is loading.
function TableSkeleton() {
    return Array.from({ length: 5 }, (_, i) => (
        <tr key={i} className="yp-an-skeleton-row">
            {Array.from({ length: 6 }, (_, j) => (
                <td key={j}>
                    <span
                        className="yp-an-skeleton"
                        style={{ width: j === 0 ? "85%" : "65%" }}
                    />
                </td>
            ))}
        </tr>
    ));
}

// Build the multipart payload accepted by the Laravel store/update endpoints.
function buildPayload(form, action) {
    const payload = new FormData();
    payload.append("title", form.title.trim());
    payload.append("message", form.message.trim());
    payload.append("priority", form.priority);
    payload.append("target_type", form.target_type);
    if (form.expires_at) payload.append("expires_at", form.expires_at);
    if (form.target_type === "selected")
        form.field_agent_ids.forEach((id) =>
            payload.append("field_agent_ids[]", String(id)),
        );
    if (form.attachment) payload.append("attachment", form.attachment);
    if (action) payload.append("action", action);
    return payload;
}

// Render the create/edit announcement drawer and its agent picker.
function AnnouncementDrawer({ open, item, onClose, onSaved, notify }) {
    const [form, setForm] = useState(EMPTY_FORM);
    const [agents, setAgents] = useState([]);
    const [agentsLoading, setAgentsLoading] = useState(false);
    const [agentSearch, setAgentSearch] = useState("");
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState("");
    const editing = Boolean(item?.id);

    // Populate the form from the selected draft or reset it for creation.
    useEffect(() => {
        if (!open) return;
        setForm(
            item
                ? {
                      title: item.title || "",
                      message: item.message || "",
                      priority: item.priority || "normal",
                      target_type: item.target_type || "all",
                      field_agent_ids: (item.recipients || []).map((r) =>
                          Number(r.field_agent_id),
                      ),
                      expires_at: toLocalInput(item.expires_at),
                      attachment: null,
                  }
                : { ...EMPTY_FORM, field_agent_ids: [] },
        );
        setError("");
        setAgentSearch("");
    }, [open, item]);

    // Load only agents belonging to the logged-in partner when the drawer opens.
    useEffect(() => {
        if (!open) return;
        const controller = new AbortController();
        setAgentsLoading(true);
        fetch(`${API}/field-agents`, {
            credentials: "same-origin",
            headers: { Accept: "application/json" },
            signal: controller.signal,
        })
            .then(readResponse)
            .then((result) => setAgents(result.field_agents || []))
            .catch((err) => {
                if (err.name !== "AbortError") setError(err.message);
            })
            .finally(() => {
                if (!controller.signal.aborted) setAgentsLoading(false);
            });
        return () => controller.abort();
    }, [open]);

    const filteredAgents = useMemo(
        () =>
            agents.filter((agent) =>
                `${agent.name || ""} ${agent.csc_id || ""}`
                    .toLowerCase()
                    .includes(agentSearch.toLowerCase()),
            ),
        [agents, agentSearch],
    );

    // Update one form field without discarding the remaining fields.
    const change = (key, value) =>
        setForm((previous) => ({ ...previous, [key]: value }));

    // Toggle an individual field agent in the selected recipient set.
    const toggleAgent = (id) =>
        setForm((previous) => ({
            ...previous,
            field_agent_ids: previous.field_agent_ids.includes(id)
                ? previous.field_agent_ids.filter((x) => x !== id)
                : [...previous.field_agent_ids, id],
        }));

    // Submit a new draft/publication or update the current draft.
    const save = async (action) => {
        if (!form.title.trim() || !form.message.trim()) {
            setError("Please enter an announcement title and message.");
            return;
        }
        if (
            form.target_type === "selected" &&
            form.field_agent_ids.length === 0
        ) {
            setError("Select at least one Field Agent.");
            return;
        }
        if (
            form.expires_at &&
            new Date(form.expires_at).getTime() <= Date.now()
        ) {
            setError("Expiry must be in the future.");
            return;
        }
        setSaving(true);
        setError("");
        try {
            const url = editing ? `${API}/${item.id}` : API;
            const payload = buildPayload(form, editing ? null : action);
            // Laravel reliably accepts multipart form submissions via method spoofing.
            if (editing) payload.append("_method", "PUT");
            const result = await readResponse(
                await fetch(url, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                    body: payload,
                }),
            );
            if (editing && action === "publish") {
                await readResponse(
                    await fetch(`${API}/${item.id}/publish`, {
                        method: "POST",
                        credentials: "same-origin",
                        headers: { Accept: "application/json" },
                    }),
                );
            }
            notify(
                editing
                    ? action === "publish"
                        ? "Draft updated and published."
                        : "Draft updated successfully."
                    : result.message || "Announcement saved.",
                "success",
            );
            onSaved();
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    };

    if (!open) return null;
    return (
        <div
            className="yp-an-overlay"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget && !saving) onClose();
            }}
        >
            <aside
                className="yp-an-drawer"
                role="dialog"
                aria-modal="true"
                aria-label={
                    editing ? "Edit announcement" : "Create announcement"
                }
            >
                <div className="yp-an-drawer-head">
                    <div className="yp-an-drawer-symbol">
                        <Megaphone size={22} />
                    </div>
                    <div>
                        <h3>
                            {editing ? "Edit Draft" : "Create Announcement"}
                        </h3>
                        <p>Communicate clearly with your field agents</p>
                    </div>
                    <button
                        className="yp-an-icon-button"
                        type="button"
                        onClick={onClose}
                        disabled={saving}
                        aria-label="Close"
                    >
                        <X size={20} />
                    </button>
                </div>
                <div className="yp-an-drawer-body">
                    {error && (
                        <div className="yp-an-alert">
                            <AlertCircle size={17} />
                            {error}
                        </div>
                    )}
                    <div className="yp-an-section-heading">
                        <span>01</span> Announcement details
                    </div>
                    <label className="yp-an-label">
                        Announcement title <em>*</em>
                        <input
                            maxLength={200}
                            value={form.title}
                            onChange={(e) => change("title", e.target.value)}
                            placeholder="e.g. Monthly Field Agent Training"
                        />
                    </label>
                    <label className="yp-an-label">
                        Message <em>*</em>
                        <textarea
                            rows={6}
                            value={form.message}
                            onChange={(e) => change("message", e.target.value)}
                            placeholder="Write a clear and helpful announcement..."
                        />
                        <small>
                            {form.message.length.toLocaleString("en-IN")}{" "}
                            characters
                        </small>
                    </label>
                    <div className="yp-an-label">
                        Priority
                        <div className="yp-an-priorities">
                            {["normal", "important", "urgent"].map(
                                (priority) => (
                                    <button
                                        key={priority}
                                        type="button"
                                        className={`yp-an-priority yp-an-priority--${priority} ${form.priority === priority ? "is-active" : ""}`}
                                        onClick={() =>
                                            change("priority", priority)
                                        }
                                    >
                                        <span className="yp-an-priority-dot" />
                                        {priority.replace(/^./, (c) =>
                                            c.toUpperCase(),
                                        )}
                                    </button>
                                ),
                            )}
                        </div>
                    </div>
                    <div className="yp-an-section-heading">
                        <span>02</span> Audience selection
                    </div>
                    <div className="yp-an-audience-options">
                        <button
                            type="button"
                            className={`yp-an-audience ${form.target_type === "all" ? "is-active" : ""}`}
                            onClick={() => change("target_type", "all")}
                        >
                            <Users size={19} />
                            <strong>All Field Agents</strong>
                            <small>Everyone in your partner network</small>
                        </button>
                        <button
                            type="button"
                            className={`yp-an-audience ${form.target_type === "selected" ? "is-active" : ""}`}
                            onClick={() => change("target_type", "selected")}
                        >
                            <UserRound size={19} />
                            <strong>Selected Agents</strong>
                            <small>Choose individual recipients</small>
                        </button>
                    </div>
                    {form.target_type === "selected" && (
                        <div className="yp-an-agent-picker">
                            <div className="yp-an-agent-picker-top">
                                <strong>Select recipients</strong>
                                <span>
                                    {form.field_agent_ids.length} selected
                                </span>
                            </div>
                            <div className="yp-an-agent-search">
                                <Search size={16} />
                                <input
                                    value={agentSearch}
                                    onChange={(e) =>
                                        setAgentSearch(e.target.value)
                                    }
                                    placeholder="Search name or CSC ID..."
                                />
                            </div>
                            <div className="yp-an-agent-actions">
                                <button
                                    type="button"
                                    onClick={() =>
                                        change("field_agent_ids", [
                                            ...new Set([
                                                ...form.field_agent_ids,
                                                ...filteredAgents.map((a) =>
                                                    Number(a.id),
                                                ),
                                            ]),
                                        ])
                                    }
                                >
                                    Select visible
                                </button>
                                <button
                                    type="button"
                                    onClick={() =>
                                        change("field_agent_ids", [])
                                    }
                                >
                                    Clear selection
                                </button>
                            </div>
                            <div className="yp-an-agent-list">
                                {agentsLoading ? (
                                    <p className="yp-an-muted">
                                        Loading Field Agents...
                                    </p>
                                ) : filteredAgents.length === 0 ? (
                                    <p className="yp-an-muted">
                                        No matching Field Agents.
                                    </p>
                                ) : (
                                    filteredAgents.map((agent) => {
                                        const id = Number(agent.id);
                                        return (
                                            <label
                                                className="yp-an-agent"
                                                key={id}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={form.field_agent_ids.includes(
                                                        id,
                                                    )}
                                                    onChange={() =>
                                                        toggleAgent(id)
                                                    }
                                                />
                                                <span className="yp-an-agent-avatar">
                                                    {(agent.name || "A")
                                                        .slice(0, 1)
                                                        .toUpperCase()}
                                                </span>
                                                <span>
                                                    <strong>
                                                        {agent.name ||
                                                            `Agent #${id}`}
                                                    </strong>
                                                    <small>
                                                        {agent.csc_id ||
                                                            `ID #${id}`}
                                                    </small>
                                                </span>
                                                <Check
                                                    size={15}
                                                    className="yp-an-agent-check"
                                                />
                                            </label>
                                        );
                                    })
                                )}
                            </div>
                        </div>
                    )}
                    {form.target_type === "all" && (
                        <p className="yp-an-hint">
                            <Users size={15} />
                            All current Partner Field Agents will be assigned
                            when you publish.
                        </p>
                    )}
                    <div className="yp-an-section-heading">
                        <span>03</span> Publishing options
                    </div>
                    <label className="yp-an-label">
                        Expiry date{" "}
                        <span className="yp-an-optional">Optional</span>
                        <input
                            type="datetime-local"
                            value={form.expires_at}
                            onChange={(e) =>
                                change("expires_at", e.target.value)
                            }
                        />
                    </label>
                    <label className="yp-an-label">
                        Attachment{" "}
                        <span className="yp-an-optional">
                            Optional · PDF, JPG, PNG · max 10 MB
                        </span>
                        <div className="yp-an-file-input">
                            <Paperclip size={17} />
                            <span>
                                {form.attachment?.name ||
                                    (editing && item?.attachment_name) ||
                                    "Choose an attachment"}
                            </span>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                onChange={(e) =>
                                    change(
                                        "attachment",
                                        e.target.files?.[0] || null,
                                    )
                                }
                            />
                        </div>
                    </label>
                    {editing && item?.attachment_path && (
                        <p className="yp-an-hint">
                            Leave the attachment unchanged to keep the existing
                            file.
                        </p>
                    )}
                </div>
                <div className="yp-an-drawer-footer">
                    <button
                        type="button"
                        className="yp-an-btn yp-an-btn--secondary"
                        disabled={saving}
                        onClick={() => save("draft")}
                    >
                        {saving ? (
                            <LoaderCircle className="yp-an-spin" size={16} />
                        ) : (
                            <FilePenLine size={16} />
                        )}
                        {editing ? "Save Changes" : "Save Draft"}
                    </button>
                    <button
                        type="button"
                        className="yp-an-btn yp-an-btn--primary"
                        disabled={saving}
                        onClick={() => save("publish")}
                    >
                        {saving ? (
                            <LoaderCircle className="yp-an-spin" size={16} />
                        ) : (
                            <Send size={16} />
                        )}
                        {editing ? "Save & Publish" : "Publish Now"}
                    </button>
                </div>
            </aside>
        </div>
    );
}

// Display the full announcement details and read-tracking snapshot.
function DetailDrawer({ item, onClose, onEdit, onPublish, onDelete, busy }) {
    if (!item) return null;
    const status = visibleStatus(item);
    const recipients = item.recipients || [];
    const readCount = Number(
        item.reads_count ?? recipients.filter((r) => r.read).length,
    );
    const total = Number(item.recipients_count ?? recipients.length);
    return (
        <div
            className="yp-an-overlay"
            onMouseDown={(e) => {
                if (e.target === e.currentTarget) onClose();
            }}
        >
            <aside
                className="yp-an-drawer yp-an-detail"
                role="dialog"
                aria-modal="true"
                aria-label="Announcement details"
            >
                <div className="yp-an-drawer-head">
                    <div className="yp-an-drawer-symbol">
                        <FileText size={22} />
                    </div>
                    <div>
                        <h3>Announcement Details</h3>
                        <p>Audience and delivery overview</p>
                    </div>
                    <button
                        className="yp-an-icon-button"
                        onClick={onClose}
                        aria-label="Close"
                    >
                        <X size={20} />
                    </button>
                </div>
                <div className="yp-an-drawer-body">
                    <div className="yp-an-detail-badges">
                        <Pill value={status} />
                        <Pill kind="priority" value={item.priority} />
                    </div>
                    <h2 className="yp-an-detail-title">{item.title}</h2>
                    <div className="yp-an-detail-message">{item.message}</div>
                    <div className="yp-an-detail-grid">
                        <div>
                            <small>Audience</small>
                            <strong>
                                {item.target_type === "all"
                                    ? "All Field Agents"
                                    : "Selected Field Agents"}
                            </strong>
                        </div>
                        <div>
                            <small>Published on</small>
                            <strong>{formatDate(item.published_at)}</strong>
                        </div>
                        <div>
                            <small>Expires on</small>
                            <strong>{formatDate(item.expires_at)}</strong>
                        </div>
                        <div>
                            <small>Created on</small>
                            <strong>{formatDate(item.created_at)}</strong>
                        </div>
                    </div>
                    <div className="yp-an-read-card">
                        <div>
                            <Users size={18} />
                            <span>Recipients</span>
                            <strong>{total.toLocaleString("en-IN")}</strong>
                        </div>
                        <div>
                            <Eye size={18} />
                            <span>Read</span>
                            <strong>{readCount.toLocaleString("en-IN")}</strong>
                        </div>
                        <div className="yp-an-progress-track">
                            <span
                                style={{
                                    width: `${total ? Math.min(100, (readCount / total) * 100) : 0}%`,
                                }}
                            />
                        </div>
                        <small>
                            {total ? Math.round((readCount / total) * 100) : 0}%
                            read rate
                        </small>
                    </div>
                    {item.attachment_path && (
                        <a
                            className="yp-an-attachment-link"
                            href={`${API}/${item.id}/attachment`}
                        >
                            <Download size={16} />
                            {item.attachment_name || "Download attachment"}
                        </a>
                    )}
                    {recipients.length > 0 && (
                        <>
                            <div className="yp-an-section-heading">
                                <span>
                                    <Users size={13} />
                                </span>{" "}
                                Recipient status
                            </div>
                            <div className="yp-an-recipient-list">
                                {recipients.map((r) => (
                                    <div key={r.id}>
                                        <span>
                                            {r.field_agent?.name ||
                                                `Agent #${r.field_agent_id}`}
                                            <small>
                                                {r.field_agent?.csc_id || ""}
                                            </small>
                                        </span>
                                        <span
                                            className={
                                                r.read
                                                    ? "yp-an-read-yes"
                                                    : "yp-an-read-no"
                                            }
                                        >
                                            {r.read ? "Read" : "Unread"}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </>
                    )}
                </div>
                <div className="yp-an-drawer-footer">
                    {status === "draft" && (
                        <>
                            <button
                                className="yp-an-btn yp-an-btn--secondary"
                                onClick={() => onEdit(item)}
                            >
                                <Pencil size={16} />
                                Edit Draft
                            </button>
                            <button
                                className="yp-an-btn yp-an-btn--primary"
                                disabled={busy}
                                onClick={() => onPublish(item)}
                            >
                                <Send size={16} />
                                Publish
                            </button>
                        </>
                    )}
                    <button
                        className="yp-an-btn yp-an-btn--danger"
                        disabled={busy}
                        onClick={() => onDelete(item)}
                    >
                        <Trash2 size={16} />
                        Delete
                    </button>
                </div>
            </aside>
        </div>
    );
}

// Render the partner's announcement management page.
export default function Announcements() {
    const [items, setItems] = useState([]);
    const [stats, setStats] = useState({});
    const [pagination, setPagination] = useState({});
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState("");
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [filter, setFilter] = useState("all");
    const [priority, setPriority] = useState("all");
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editItem, setEditItem] = useState(null);
    const [detail, setDetail] = useState(null);
    const [busy, setBusy] = useState(false);

    // Debounce the search input to avoid a request for every keystroke.
    useEffect(() => {
        const timer = setTimeout(() => setDebouncedSearch(search.trim()), 350);
        return () => clearTimeout(timer);
    }, [search]);

    // Fetch announcements and summary cards using the current filters.
    const load = useCallback(
        async (silent = false) => {
            if (silent) setRefreshing(true);
            else setLoading(true);
            try {
                const params = new URLSearchParams({ page: String(page) });
                if (debouncedSearch) params.set("search", debouncedSearch);
                if (filter !== "all") params.set("status", filter);
                if (priority !== "all") params.set("priority", priority);
                const result = await readResponse(
                    await fetch(`${API}?${params}`, {
                        credentials: "same-origin",
                        headers: { Accept: "application/json" },
                    }),
                );
                setItems(result.announcements?.data || []);
                setPagination(result.announcements || {});
                setStats(result.stats || {});
                setError("");
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [page, debouncedSearch, filter, priority],
    );

    // Refresh whenever the active page or filters change.
    useEffect(() => {
        load();
    }, [load]);

    // Show a dismissible feedback message after a mutation.
    const notify = (message, type = "success") => setNotice({ message, type });

    // Open the creation drawer with a fresh form.
    const create = () => {
        setDetail(null);
        setEditItem(null);
        setDrawerOpen(true);
    };

    // Open the edit drawer for an unpublished draft with its recipient IDs.
    const edit = (item) => {
        setDetail(null);
        setEditItem(item);
        setDrawerOpen(true);
    };

    // Reload the list after a successful create or update.
    const saved = () => {
        setDrawerOpen(false);
        setEditItem(null);
        if (page !== 1) setPage(1);
        else load(true);
    };

    // Fetch complete details, including recipients and their read status.
    const show = async (id) => {
        try {
            setBusy(true);
            const result = await readResponse(
                await fetch(`${API}/${id}`, {
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                }),
            );
            setDetail(result.announcement);
        } catch (err) {
            notify(err.message, "error");
        } finally {
            setBusy(false);
        }
    };

    // Publish an existing draft after explicit confirmation.
    const publish = async (item) => {
        if (
            !window.confirm(`Publish “${item.title}” to its selected audience?`)
        )
            return;
        try {
            setBusy(true);
            const result = await readResponse(
                await fetch(`${API}/${item.id}/publish`, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                }),
            );
            notify(result.message || "Announcement published.");
            setDetail(null);
            load(true);
        } catch (err) {
            notify(err.message, "error");
        } finally {
            setBusy(false);
        }
    };

    // Soft-delete an announcement only after the partner confirms.
    const remove = async (item) => {
        if (
            !window.confirm(
                `Delete “${item.title}”? This announcement will be removed from the listing.`,
            )
        )
            return;
        try {
            setBusy(true);
            const result = await readResponse(
                await fetch(`${API}/${item.id}`, {
                    method: "DELETE",
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                }),
            );
            notify(result.message || "Announcement deleted.");
            setDetail(null);
            if (items.length === 1 && page > 1) setPage((p) => p - 1);
            else load(true);
        } catch (err) {
            notify(err.message, "error");
        } finally {
            setBusy(false);
        }
    };

    const cards = [
        {
            label: "Total Announcements",
            sub: "All communications",
            value: stats.total ?? 0,
            icon: Megaphone,
            color: "blue",
            tab: "all",
        },
        {
            label: "Published",
            sub: "Successfully published",
            value: stats.published ?? 0,
            icon: CheckCircle2,
            color: "green",
            tab: "published",
        },
        {
            label: "Drafts",
            sub: "Awaiting publication",
            value: stats.draft ?? 0,
            icon: FilePenLine,
            color: "amber",
            tab: "draft",
        },
        {
            label: "Expired",
            sub: "Past expiry date",
            value: stats.expired ?? 0,
            icon: Clock3,
            color: "purple",
            tab: "expired",
        },
    ];

    return (
        <div className="yp-an-page">
            <header className="yp-an-header">
                <div className="yp-an-heading">
                    <span className="yp-an-eyebrow">
                        <Megaphone size={14} /> PARTNER COMMUNICATION HUB
                    </span>
                    <h2>Announcements</h2>
                    <p>
                        Create and manage important updates for your Field Agent
                        network.
                    </p>
                </div>
                <div className="yp-an-header-actions">
                    <button
                        className="yp-an-btn yp-an-btn--secondary"
                        disabled={refreshing}
                        onClick={() => load(true)}
                    >
                        <RefreshCw
                            size={16}
                            className={refreshing ? "yp-an-spin" : ""}
                        />
                        Refresh
                    </button>
                    <button
                        className="yp-an-btn yp-an-btn--primary"
                        onClick={create}
                    >
                        <Plus size={18} />
                        New Announcement
                    </button>
                </div>
            </header>
            {notice && (
                <div
                    className={`yp-an-notice yp-an-notice--${notice.type}`}
                    role="status"
                >
                    {notice.type === "error" ? (
                        <AlertCircle size={17} />
                    ) : (
                        <CheckCircle2 size={17} />
                    )}
                    <span>{notice.message}</span>
                    <button
                        onClick={() => setNotice(null)}
                        aria-label="Dismiss"
                    >
                        <X size={16} />
                    </button>
                </div>
            )}
            <div className="yp-an-stats">
                {cards.map((card) => {
                    const Icon = card.icon;
                    return (
                        <button
                            key={card.tab}
                            type="button"
                            className={`yp-an-stat yp-an-stat--${card.color} ${filter === card.tab ? "is-selected" : ""}`}
                            onClick={() => {
                                setFilter(card.tab);
                                setPage(1);
                            }}
                        >
                            <span className="yp-an-stat-icon">
                                <Icon size={21} />
                            </span>
                            <span className="yp-an-stat-info">
                                <small>{card.label}</small>
                                <strong>
                                    {loading ? (
                                        <span className="yp-an-skeleton yp-an-skeleton-number" />
                                    ) : (
                                        Number(card.value).toLocaleString(
                                            "en-IN",
                                        )
                                    )}
                                </strong>
                                <span>{card.sub}</span>
                            </span>
                            <ChevronRight
                                size={17}
                                className="yp-an-stat-chevron"
                            />
                        </button>
                    );
                })}
            </div>
            <section className="yp-an-panel">
                <div className="yp-an-panel-top">
                    <div>
                        <h3>Announcement Management</h3>
                        <p>
                            View delivery, readership and publishing status in
                            one place.
                        </p>
                    </div>
                    <span className="yp-an-panel-count">
                        {Number(pagination.total ?? 0).toLocaleString("en-IN")}{" "}
                        results
                    </span>
                </div>
                <div className="yp-an-toolbar">
                    <label className="yp-an-search">
                        <Search size={18} />
                        <input
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1);
                            }}
                            placeholder="Search announcement title or message..."
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => {
                                    setSearch("");
                                    setPage(1);
                                }}
                                aria-label="Clear search"
                            >
                                <X size={15} />
                            </button>
                        )}
                    </label>
                    <label className="yp-an-select">
                        <Filter size={16} />
                        <select
                            value={priority}
                            onChange={(e) => {
                                setPriority(e.target.value);
                                setPage(1);
                            }}
                            aria-label="Filter by priority"
                        >
                            <option value="all">All Priorities</option>
                            <option value="normal">Normal</option>
                            <option value="important">Important</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </label>
                </div>
                <div
                    className="yp-an-tabs"
                    role="group"
                    aria-label="Announcement status filters"
                >
                    {TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            className={filter === tab.value ? "is-active" : ""}
                            onClick={() => {
                                setFilter(tab.value);
                                setPage(1);
                            }}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>
                {error && (
                    <div className="yp-an-alert">
                        <AlertCircle size={17} />
                        {error}
                        <button onClick={() => load(true)}>Retry</button>
                    </div>
                )}
                <div className="yp-an-table-wrap">
                    <table className="yp-an-table">
                        <thead>
                            <tr>
                                <th>Announcement</th>
                                <th>Audience</th>
                                <th>Priority</th>
                                <th>Published / Created</th>
                                <th>Status</th>
                                <th>Engagement</th>
                                <th className="yp-an-actions-th">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <TableSkeleton />
                            ) : items.length === 0 ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="yp-an-empty">
                                            <span>
                                                <Megaphone size={27} />
                                            </span>
                                            <h4>No announcements found</h4>
                                            <p>
                                                {search ||
                                                filter !== "all" ||
                                                priority !== "all"
                                                    ? "Try changing your search or filters."
                                                    : "Publish your first update to connect with your Field Agents."}
                                            </p>
                                            <button
                                                className="yp-an-btn yp-an-btn--primary"
                                                onClick={create}
                                            >
                                                <Plus size={16} />
                                                Create Announcement
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                items.map((item) => {
                                    const status = visibleStatus(item);
                                    const total = Number(
                                        item.recipients_count || 0,
                                    );
                                    const read = Number(item.reads_count || 0);
                                    return (
                                        <tr key={item.id}>
                                            <td>
                                                <button
                                                    className="yp-an-title-cell"
                                                    onClick={() =>
                                                        show(item.id)
                                                    }
                                                >
                                                    <span className="yp-an-row-icon">
                                                        <Megaphone size={18} />
                                                    </span>
                                                    <span>
                                                        <strong>
                                                            {item.title}
                                                        </strong>
                                                        <small>
                                                            {item.message}
                                                        </small>
                                                        <em>#{item.id}</em>
                                                    </span>
                                                </button>
                                            </td>
                                            <td>
                                                <span className="yp-an-audience-label">
                                                    <Users size={15} />
                                                    {item.target_type === "all"
                                                        ? "All Agents"
                                                        : "Selected Agents"}
                                                </span>
                                                <small className="yp-an-subline">
                                                    {total.toLocaleString(
                                                        "en-IN",
                                                    )}{" "}
                                                    recipients
                                                </small>
                                            </td>
                                            <td>
                                                <Pill
                                                    kind="priority"
                                                    value={item.priority}
                                                />
                                            </td>
                                            <td>
                                                <span className="yp-an-date">
                                                    <CalendarDays size={14} />
                                                    {formatDate(
                                                        item.published_at ||
                                                            item.created_at,
                                                    )}
                                                </span>
                                            </td>
                                            <td>
                                                <Pill value={status} />
                                            </td>
                                            <td>
                                                <div className="yp-an-engagement">
                                                    <strong>
                                                        {read.toLocaleString(
                                                            "en-IN",
                                                        )}{" "}
                                                        <span>
                                                            /{" "}
                                                            {total.toLocaleString(
                                                                "en-IN",
                                                            )}
                                                        </span>
                                                    </strong>
                                                    <div className="yp-an-progress-track">
                                                        <span
                                                            style={{
                                                                width: `${total ? Math.min(100, (read / total) * 100) : 0}%`,
                                                            }}
                                                        />
                                                    </div>
                                                    <small>
                                                        {status === "draft"
                                                            ? "Not published"
                                                            : `${total ? Math.round((read / total) * 100) : 0}% read`}
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div className="yp-an-row-actions">
                                                    <button
                                                        title="View details"
                                                        aria-label={`View ${item.title}`}
                                                        disabled={busy}
                                                        onClick={() =>
                                                            show(item.id)
                                                        }
                                                    >
                                                        <Eye size={16} />
                                                    </button>
                                                    {status === "draft" && (
                                                        <button
                                                            title="Edit draft"
                                                            aria-label={`Edit ${item.title}`}
                                                            disabled={busy}
                                                            onClick={async () => {
                                                                try {
                                                                    setBusy(
                                                                        true,
                                                                    );
                                                                    const result =
                                                                        await readResponse(
                                                                            await fetch(
                                                                                `${API}/${item.id}`,
                                                                                {
                                                                                    credentials:
                                                                                        "same-origin",
                                                                                    headers:
                                                                                        {
                                                                                            Accept: "application/json",
                                                                                        },
                                                                                },
                                                                            ),
                                                                        );
                                                                    edit(
                                                                        result.announcement,
                                                                    );
                                                                } catch (err) {
                                                                    notify(
                                                                        err.message,
                                                                        "error",
                                                                    );
                                                                } finally {
                                                                    setBusy(
                                                                        false,
                                                                    );
                                                                }
                                                            }}
                                                        >
                                                            <Pencil size={16} />
                                                        </button>
                                                    )}
                                                    {status === "draft" && (
                                                        <button
                                                            title="Publish draft"
                                                            aria-label={`Publish ${item.title}`}
                                                            disabled={busy}
                                                            onClick={() =>
                                                                publish(item)
                                                            }
                                                        >
                                                            <Send size={16} />
                                                        </button>
                                                    )}
                                                    <button
                                                        className="yp-an-delete-action"
                                                        title="Delete"
                                                        aria-label={`Delete ${item.title}`}
                                                        disabled={busy}
                                                        onClick={() =>
                                                            remove(item)
                                                        }
                                                    >
                                                        <Trash2 size={16} />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
                <footer className="yp-an-footer">
                    <span>
                        Showing{" "}
                        {items.length
                            ? ((pagination.current_page || page) - 1) *
                                  (pagination.per_page || 10) +
                              1
                            : 0}
                        –
                        {items.length
                            ? ((pagination.current_page || page) - 1) *
                                  (pagination.per_page || 10) +
                              items.length
                            : 0}{" "}
                        of{" "}
                        {Number(pagination.total || 0).toLocaleString("en-IN")}
                    </span>
                    <div className="yp-an-pagination">
                        <button
                            disabled={page <= 1 || loading}
                            onClick={() => setPage((p) => p - 1)}
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>
                        <span>
                            Page {pagination.current_page || page} of{" "}
                            {pagination.last_page || 1}
                        </span>
                        <button
                            disabled={
                                page >= (pagination.last_page || 1) || loading
                            }
                            onClick={() => setPage((p) => p + 1)}
                            aria-label="Next page"
                        >
                            <ChevronRight size={17} />
                        </button>
                    </div>
                </footer>
            </section>
            <AnnouncementDrawer
                open={drawerOpen}
                item={editItem}
                onClose={() => setDrawerOpen(false)}
                onSaved={saved}
                notify={notify}
            />
            <DetailDrawer
                item={detail}
                onClose={() => setDetail(null)}
                onEdit={edit}
                onPublish={publish}
                onDelete={remove}
                busy={busy}
            />
        </div>
    );
}
