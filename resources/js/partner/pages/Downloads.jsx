import React, { useCallback, useEffect, useState } from "react";

import {
    Download,
    FileSpreadsheet,
    Search,
    RefreshCw,
    CheckCircle2,
    Clock3,
    AlertCircle,
    Files,
    ChevronLeft,
    ChevronRight,
} from "lucide-react";

import "./Downloads.css";

const API = "/api/partner/downloads";

const FILTERS = [
    { value: "all", label: "All Exports" },
    { value: "completed", label: "Completed" },
    { value: "processing", label: "Processing" },
    { value: "failed", label: "Failed" },
];

// Format the estimated time remaining for an export.
function getPreparingLabel(seconds) {
    if (seconds == null || !Number.isFinite(Number(seconds))) {
        return "Preparing...";
    }

    const remaining = Math.max(0, Math.ceil(Number(seconds)));

    if (remaining < 60) {
        return `Preparing... · ${remaining}s left`;
    }

    const minutes = Math.ceil(remaining / 60);

    return `Preparing... · ~${minutes} min left`;
}

function StatusBadge({ status }) {
    const value = status || "queued";

    return (
        <span className={`yp-dl-status yp-dl-status--${value}`}>
            <span className="yp-dl-status-dot" />
            {value === "queued"
                ? "Queued"
                : value.charAt(0).toUpperCase() + value.slice(1)}
        </span>
    );
}

/* =========================================
   DOWNLOAD TABLE SKELETON
========================================= */

function DownloadsSkeleton({ rows = 6 }) {
    return Array.from({ length: rows }, (_, index) => (
        <tr className="yp-dl-skeleton-row" key={index}>
            <td>
                <div className="yp-dl-skeleton-file">
                    <span className="yp-dl-skeleton yp-dl-sk-icon" />

                    <div className="yp-dl-sk-lines">
                        <span className="yp-dl-skeleton yp-dl-sk-title" />
                        <span className="yp-dl-skeleton yp-dl-sk-subtitle" />
                    </div>
                </div>
            </td>

            <td>
                <span className="yp-dl-skeleton yp-dl-sk-records" />
            </td>

            <td>
                <span className="yp-dl-skeleton yp-dl-sk-date" />
            </td>

            <td>
                <span className="yp-dl-skeleton yp-dl-sk-badge" />
            </td>

            <td>
                <span className="yp-dl-skeleton yp-dl-sk-button" />
            </td>
        </tr>
    ));
}

export default function Downloads() {
    const [exports, setExports] = useState([]);
    const [stats, setStats] = useState({});
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const [search, setSearch] = useState("");
    const [filter, setFilter] = useState("all");
    const [page, setPage] = useState(1);
    const [pagination, setPagination] = useState({});
    const [error, setError] = useState("");

    const loadDownloads = useCallback(
        async (silent = false) => {
            if (silent) setRefreshing(true);
            else setLoading(true);

            try {
                const params = new URLSearchParams({
                    page: String(page),
                });

                if (search.trim()) {
                    params.set("search", search.trim());
                }

                if (filter !== "all") {
                    params.set("status", filter);
                }

                const response = await fetch(`${API}?${params.toString()}`, {
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (!response.ok) {
                    throw new Error("Unable to load downloads.");
                }

                const result = await response.json();

                setExports(result.exports?.data || []);
                setPagination(result.exports || {});
                setStats(result.stats || {});
                setError("");
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [page, search, filter],
    );

    useEffect(() => {
        loadDownloads();
    }, [loadDownloads]);

    // Automatically refresh while exports are pending.
    useEffect(() => {
        const hasPending = exports.some((item) =>
            ["queued", "processing"].includes(item.status),
        );

        if (!hasPending) return;

        const interval = setInterval(() => {
            loadDownloads(true);
        }, 8000);

        return () => clearInterval(interval);
    }, [exports, loadDownloads]);

    const downloadFile = (item) => {
        if (item.status !== "completed") return;

        window.location.href = `${API}/${item.id}/download`;
    };

    const cards = [
        {
            label: "Total Exports",
            description: "All export requests",
            value: stats.total ?? 0,
            icon: Files,
            color: "blue",
            filter: "all",
        },
        {
            label: "Completed Exports",
            description: "Ready to download",
            value: stats.completed ?? 0,
            icon: CheckCircle2,
            color: "green",
            filter: "completed",
        },
        {
            label: "Processing Exports",
            description: "Preparing export files",
            value: stats.processing ?? 0,
            icon: Clock3,
            color: "purple",
            filter: "processing",
        },
        {
            label: "Failed Exports",
            description: "Unsuccessful requests",
            value: stats.failed ?? 0,
            icon: AlertCircle,
            color: "red",
            filter: "failed",
        },
    ];

    return (
        <div className="yp-dl-page">
            <div className="yp-dl-header">
                <div>
                    <h2>My Downloads</h2>
                    <p>
                        Manage your exported reports and download completed
                        files.
                    </p>
                </div>

                <button
                    className="yp-dl-refresh"
                    onClick={() => loadDownloads(true)}
                    disabled={refreshing}
                >
                    <RefreshCw
                        size={16}
                        className={refreshing ? "yp-dl-spin" : ""}
                    />
                    Refresh
                </button>
            </div>

            <div className="yp-dl-stats">
                {cards.map((card) => {
                    const Icon = card.icon;

                    return (
                        <button
                            key={card.label}
                            type="button"
                            className={`yp-dl-stat yp-dl-stat--${card.color} ${
                                filter === card.filter ? "is-selected" : ""
                            }`}
                            onClick={() => {
                                setFilter(card.filter);
                                setPage(1);
                            }}
                        >
                            <div className="yp-dl-stat-icon">
                                <Icon size={21} strokeWidth={2} />
                            </div>

                            <div className="yp-dl-stat-content">
                                <span className="yp-dl-stat-title">
                                    {card.label}
                                </span>

                                {loading ? (
                                    <span className="yp-dl-skeleton yp-dl-sk-stat-value" />
                                ) : (
                                    <strong>
                                        {Number(card.value).toLocaleString(
                                            "en-IN",
                                        )}
                                    </strong>
                                )}

                                <small>{card.description}</small>
                            </div>

                            <span className="yp-dl-stat-arrow">
                                <ChevronRight size={17} />
                            </span>
                        </button>
                    );
                })}
            </div>

            <div className="yp-dl-panel">
                <div className="yp-dl-toolbar">
                    <div className="yp-dl-search">
                        <Search size={17} />

                        <input
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1);
                            }}
                            placeholder="Search export files..."
                        />
                    </div>

                    <div className="yp-dl-filters">
                        {FILTERS.map((item) => (
                            <button
                                key={item.value}
                                className={
                                    filter === item.value ? "active" : ""
                                }
                                onClick={() => {
                                    setFilter(item.value);
                                    setPage(1);
                                }}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>
                </div>

                {error && <div className="yp-dl-error">{error}</div>}

                <div className="yp-dl-table-wrap">
                    <table className="yp-dl-table">
                        <thead>
                            <tr>
                                <th>Export File</th>
                                <th>Records</th>
                                <th>Requested On</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                <DownloadsSkeleton rows={6} />
                            ) : exports.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="yp-dl-empty">
                                        No export files found.
                                    </td>
                                </tr>
                            ) : (
                                exports.map((item) => (
                                    <tr key={item.id}>
                                        <td>
                                            <div className="yp-dl-file">
                                                <span className="yp-dl-file-icon">
                                                    <FileSpreadsheet
                                                        size={20}
                                                    />
                                                </span>

                                                <div>
                                                    <strong>
                                                        {item.title ||
                                                            item.file_name ||
                                                            `Export #${item.id}`}
                                                    </strong>

                                                    <small>
                                                        Export ID #{item.id}
                                                        {" · "}
                                                        {(
                                                            item.type || "CSV"
                                                        ).toUpperCase()}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            {Number(
                                                item.total_records || 0,
                                            ).toLocaleString("en-IN")}
                                        </td>

                                        <td>
                                            {item.created_at
                                                ? new Date(
                                                      item.created_at,
                                                  ).toLocaleString("en-IN")
                                                : "—"}
                                        </td>

                                        <td>
                                            <StatusBadge status={item.status} />
                                        </td>

                                        <td>
                                            {item.status === "completed" ? (
                                                <button
                                                    className="yp-dl-download"
                                                    onClick={() =>
                                                        downloadFile(item)
                                                    }
                                                >
                                                    <Download size={15} />
                                                    Download
                                                </button>
                                            ) : item.status === "failed" ? (
                                                <span
                                                    className="yp-dl-failed"
                                                    title={
                                                        item.error_message ||
                                                        "Export failed"
                                                    }
                                                >
                                                    Failed
                                                </span>
                                            ) : (
                                                <div className="yp-dl-preparing">
                                                    <span className="yp-dl-wait">
                                                        <Clock3 size={14} />
                                                        {getPreparingLabel(
                                                            item.estimated_seconds_remaining,
                                                        )}
                                                    </span>

                                                    {Number(
                                                        item.total_records,
                                                    ) > 0 && (
                                                        <small className="yp-dl-progress-text">
                                                            {Number(
                                                                item.processed_records ||
                                                                    0,
                                                            ).toLocaleString(
                                                                "en-IN",
                                                            )}
                                                            {" / "}
                                                            {Number(
                                                                item.total_records,
                                                            ).toLocaleString(
                                                                "en-IN",
                                                            )}
                                                            {" records"}
                                                        </small>
                                                    )}
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="yp-dl-footer">
                    <span>
                        Page {pagination.current_page || 1}
                        {" of "}
                        {pagination.last_page || 1}
                    </span>

                    <div className="yp-dl-pagination">
                        <button
                            disabled={page <= 1 || loading}
                            onClick={() => setPage((p) => p - 1)}
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={17} />
                        </button>

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
                </div>
            </div>
        </div>
    );
}
