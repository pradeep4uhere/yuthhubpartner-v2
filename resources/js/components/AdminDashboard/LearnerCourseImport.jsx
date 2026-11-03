import React, { useCallback, useEffect, useState } from "react";
import {
    Activity,
    AlertCircle,
    CheckCircle2,
    Clock3,
    Database,
    Download,
    FileSpreadsheet,
    History,
    RefreshCw,
    Search,
    UploadCloud,
    Users,
    XCircle,
} from "lucide-react";

import Sidebar from "./Sidebar";
import DashboardHeader from "./DashboardHeader";

import "../../../css/admin-learner-course-import.css";

/* Format numbers consistently for dashboard metrics. */
const formatNumber = (value) =>
    new Intl.NumberFormat("en-IN").format(Number(value || 0));

/* Format timestamps for batch history and record details. */
const formatDate = (value) => {
    if (!value) return "—";

    return new Date(value).toLocaleString("en-IN", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

/* Render a batch status with its corresponding visual state. */
function ImportStatus({ status }) {
    const value = String(status || "unknown").toLowerCase();

    return (
        <span className={`lci-status lci-status--${value}`}>
            <span className="lci-status__dot" />
            {value.replaceAll("_", " ")}
        </span>
    );
}

/* Render an individual dashboard metric card. */
function ImportMetric({ label, value, icon: Icon, color }) {
    return (
        <div className={`lci-metric lci-metric--${color}`}>
            <div className="lci-metric__top">
                <span>{label}</span>
                <span className="lci-metric__icon">
                    <Icon size={19} />
                </span>
            </div>

            <strong>{formatNumber(value)}</strong>
        </div>
    );
}

/* Render metric placeholders while dashboard data is loading. */
function ImportSkeleton() {
    return (
        <div className="lci-metrics-grid">
            {[1, 2, 3, 4, 5].map((item) => (
                <div className="lci-skeleton-card" key={item}>
                    <span />
                    <strong />
                </div>
            ))}
        </div>
    );
}

/* Main Learner Course CSV Import dashboard component. */
export default function LearnerCourseImport({
    adminName = "Admin",
    csrfToken = "",
}) {
    const [summary, setSummary] = useState({});
    const [activeBatch, setActiveBatch] = useState(null);
    const [latestBatch, setLatestBatch] = useState(null);
    const [selectedBatchDetails, setSelectedBatchDetails] = useState(null);
    const [batches, setBatches] = useState([]);

    const [selectedBatch, setSelectedBatch] = useState("");
    const [records, setRecords] = useState([]);
    const [pagination, setPagination] = useState({});

    const [matchStatus, setMatchStatus] = useState("all");
    const [search, setSearch] = useState("");
    const [appliedSearch, setAppliedSearch] = useState("");
    const [page, setPage] = useState(1);

    const [selectedFile, setSelectedFile] = useState(null);
    const [dragging, setDragging] = useState(false);

    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState("");
    const [notice, setNotice] = useState("");

    /* Send an API request and safely parse its JSON response. */
    const fetchJson = useCallback(async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: "same-origin",
            ...options,
            headers: {
                Accept: "application/json",
                ...(options.headers || {}),
            },
        });

        const result = await response.json();

        if (!response.ok || result.success === false) {
            throw new Error(
                result.message || "Unable to complete the request.",
            );
        }

        return result;
    }, []);

    /* Load dashboard summary and recent import batches. */
    const fetchDashboard = useCallback(async () => {
        const result = await fetchJson("/admin/learner-course-import/data");

        setSummary(result.summary || {});
        setActiveBatch(result.active_batch || null);
        setLatestBatch(result.latest_batch || null);
        setBatches(result.batches?.data || []);

        if (!selectedBatch && result.latest_batch?.batch_id) {
            setSelectedBatch(result.latest_batch.batch_id);
        }
    }, [fetchJson, selectedBatch]);

    /* Load the selected batch status and its paginated records. */
    const fetchBatchDetails = useCallback(async () => {
        if (!selectedBatch) {
            setRecords([]);
            setPagination({});
            return;
        }

        const statusResult = await fetchJson(
            `/admin/learner-course-import/${selectedBatch}/status`,
        );

        const batch = statusResult.batch || null;

        if (batch) {
            // Keep the selected batch's live database progress separate from dashboard history.
            setSelectedBatchDetails(batch);

            if (["queued", "processing"].includes(batch.status)) {
                setActiveBatch(batch);
            } else {
                setActiveBatch((current) =>
                    current?.batch_id === batch.batch_id ? null : current,
                );
            }
        }

        const params = new URLSearchParams({
            page: String(page),
            per_page: "20",
            match_status: matchStatus,
            search: appliedSearch,
        });

        const recordsResult = await fetchJson(
            `/admin/learner-course-import/${selectedBatch}/records?${params}`,
        );

        setRecords(recordsResult.records?.data || []);
        setPagination(recordsResult.records || {});
    }, [selectedBatch, page, matchStatus, appliedSearch, fetchJson]);

    /* Refresh dashboard data while handling API errors. */
    const refreshData = useCallback(async () => {
        try {
            setError("");
            await fetchDashboard();
        } catch (err) {
            setError(err.message);
        }
    }, [fetchDashboard]);

    /* Load dashboard data initially and refresh it periodically. */
    useEffect(() => {
        let mounted = true;

        const load = async () => {
            try {
                await fetchDashboard();
            } catch (err) {
                if (mounted) {
                    setError(err.message);
                }
            } finally {
                if (mounted) {
                    setLoading(false);
                }
            }
        };

        load();

        const interval = setInterval(() => {
            if (mounted) {
                refreshData();
            }
        }, 10000);

        return () => {
            mounted = false;
            clearInterval(interval);
        };
    }, [fetchDashboard, refreshData]);

    /* Refresh the selected batch and its records periodically. */
    useEffect(() => {
        if (!selectedBatch) return;

        let mounted = true;

        const load = async () => {
            try {
                await fetchBatchDetails();
            } catch (err) {
                if (mounted) {
                    setError(err.message);
                }
            }
        };

        load();

        const interval = setInterval(load, 5000);

        return () => {
            mounted = false;
            clearInterval(interval);
        };
    }, [fetchBatchDetails]);

    /* Validate a selected CSV file before accepting it. */
    const validateFile = (file) => {
        if (!file) return;

        const extension = file.name.split(".").pop()?.toLowerCase();

        if (!["csv", "txt"].includes(extension)) {
            setError("Please select a CSV or TXT file.");
            setSelectedFile(null);
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            setError("File size must not exceed 50 MB.");
            setSelectedFile(null);
            return;
        }

        setError("");
        setNotice("");
        setSelectedFile(file);
    };

    /* Upload the selected CSV and start a new import batch. */
    const handleUpload = async () => {
        if (!selectedFile || uploading) return;

        try {
            setUploading(true);
            setError("");
            setNotice("");

            const formData = new FormData();
            formData.append("file", selectedFile);

            const result = await fetchJson(
                "/admin/learner-course-import/upload",
                {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: formData,
                },
            );

            const batchId = result.batch?.batch_id;

            setNotice(result.message || "Import has been queued successfully.");

            setSelectedFile(null);

            if (batchId) {
                setSelectedBatch(batchId);
                setSelectedBatchDetails(null);
                setPage(1);
                setMatchStatus("all");
                setSearch("");
                setAppliedSearch("");
            }

            await fetchDashboard();
        } catch (err) {
            setError(err.message);
        } finally {
            setUploading(false);
        }
    };

    /* Apply the record search filter and reset pagination. */
    const handleSearch = () => {
        setAppliedSearch(search.trim());
        setPage(1);
    };

    // Use the selected batch for progress, falling back to the latest batch.
    const progressBatch = selectedBatchDetails || latestBatch;

    // Read total, imported, and skipped rows from the selected batch.
    const totalRows = Number(progressBatch?.total_rows || 0);
    const importedRows = Number(progressBatch?.processed_rows || 0);
    const skippedRows = Number(progressBatch?.skipped_rows || 0);

    // Calculate remaining input rows after imports and invalid contacts.
    const remainingRows = Math.max(0, totalRows - importedRows - skippedRows);

    // Calculate overall input processing progress.
    const completedProgress =
        totalRows > 0
            ? Math.min(
                  100,
                  Math.round(((importedRows + skippedRows) / totalRows) * 100),
              )
            : 0;

    // Calculate the imported segment width of the progress bar.
    const importedProgress =
        totalRows > 0 ? Math.min(100, (importedRows / totalRows) * 100) : 0;

    // Calculate the skipped segment width of the progress bar.
    const skippedProgress =
        totalRows > 0
            ? Math.min(100 - importedProgress, (skippedRows / totalRows) * 100)
            : 0;

    return (
        <div className="admin-shell">
            <Sidebar adminName={adminName} />

            <main className="admin-main">
                <DashboardHeader adminName={adminName} />

                <div className="admin-content lci-content">
                    <div className="lci-heading">
                        <div>
                            <h1>Learner Course Import</h1>
                            <p className="admin-page-heading__sub">
                                Import course completion data and match learner
                                records.
                            </p>
                        </div>

                        <button
                            type="button"
                            className="lci-refresh-btn"
                            onClick={refreshData}
                        >
                            <RefreshCw size={15} />
                            Refresh
                        </button>
                    </div>

                    {/* Display API errors and allow the user to dismiss them. */}
                    {error && (
                        <div className="lci-alert lci-alert--error">
                            <AlertCircle size={17} />
                            <span>{error}</span>

                            <button type="button" onClick={() => setError("")}>
                                <XCircle size={16} />
                            </button>
                        </div>
                    )}

                    {/* Display successful upload and processing notifications. */}
                    {notice && (
                        <div className="lci-alert lci-alert--success">
                            <CheckCircle2 size={17} />
                            <span>{notice}</span>

                            <button type="button" onClick={() => setNotice("")}>
                                <XCircle size={16} />
                            </button>
                        </div>
                    )}

                    {/* CSV upload panel. */}
                    <section className="lci-upload-panel">
                        <div className="lci-section-heading">
                            <div>
                                <h2>
                                    <UploadCloud size={18} />
                                    Upload Course CSV
                                </h2>

                                <p>
                                    Select a CSV file containing learner course
                                    completion data.
                                </p>
                            </div>
                        </div>

                        <label
                            className={`lci-dropzone ${
                                dragging ? "lci-dropzone--active" : ""
                            }`}
                            onDragOver={(event) => {
                                event.preventDefault();
                                setDragging(true);
                            }}
                            onDragLeave={() => setDragging(false)}
                            onDrop={(event) => {
                                event.preventDefault();
                                setDragging(false);
                                validateFile(event.dataTransfer.files?.[0]);
                            }}
                        >
                            <input
                                type="file"
                                accept=".csv,.txt"
                                onChange={(event) =>
                                    validateFile(event.target.files?.[0])
                                }
                            />

                            <span className="lci-upload-icon">
                                <FileSpreadsheet size={27} />
                            </span>

                            <strong>
                                {selectedFile
                                    ? selectedFile.name
                                    : "Drop your CSV file here or browse"}
                            </strong>

                            <span>CSV or TXT · Maximum file size 50 MB</span>
                        </label>

                        <div className="lci-upload-actions">
                            <span>
                                {selectedFile
                                    ? `${(
                                          selectedFile.size /
                                          (1024 * 1024)
                                      ).toFixed(2)} MB selected`
                                    : "Your existing learners will be matched by contact number."}
                            </span>

                            <button
                                type="button"
                                className="lci-btn lci-btn--primary"
                                onClick={handleUpload}
                                disabled={!selectedFile || uploading}
                            >
                                {uploading ? (
                                    <>
                                        <RefreshCw
                                            size={15}
                                            className="lci-spin"
                                        />
                                        Uploading...
                                    </>
                                ) : (
                                    <>
                                        <UploadCloud size={15} />
                                        Start Import
                                    </>
                                )}
                            </button>
                        </div>
                    </section>

                    {/* Dashboard metric cards. */}
                    {loading ? (
                        <ImportSkeleton />
                    ) : (
                        <section className="lci-metrics-grid">
                            <ImportMetric
                                label="Total Imported Rows"
                                value={summary.total_processed}
                                icon={Database}
                                color="blue"
                            />

                            <ImportMetric
                                label="Matched Learners"
                                value={summary.total_matched}
                                icon={Users}
                                color="green"
                            />

                            <ImportMetric
                                label="Pending Matches"
                                value={summary.total_pending}
                                icon={Clock3}
                                color="amber"
                            />

                            <ImportMetric
                                label="Skipped Contacts"
                                value={summary.total_skipped}
                                icon={AlertCircle}
                                color="purple"
                            />

                            <ImportMetric
                                label="Import Batches"
                                value={summary.total_imports}
                                icon={History}
                                color="slate"
                            />
                        </section>
                    )}

                    {/* Selected batch processing progress. */}
                    <section className="lci-progress-panel">
                        <div className="lci-section-heading">
                            <div>
                                <h2>
                                    <Activity size={18} />
                                    Import Progress
                                </h2>

                                <p>
                                    Actual imported and skipped input rows for
                                    the selected CSV batch.
                                </p>
                            </div>

                            {progressBatch && (
                                <ImportStatus status={progressBatch.status} />
                            )}
                        </div>

                        {progressBatch ? (
                            <>
                                <div className="lci-progress-meta">
                                    <span>
                                        {progressBatch.original_filename ||
                                            "Import batch"}
                                    </span>

                                    <strong>{completedProgress}%</strong>
                                </div>

                                {/* Show imported and skipped rows as separate progress segments. */}
                                <div className="lci-progress-track lci-progress-track--split">
                                    <span
                                        className="lci-progress-fill--imported"
                                        style={{
                                            width: `${importedProgress}%`,
                                        }}
                                    />

                                    <span
                                        className="lci-progress-fill--skipped"
                                        style={{
                                            width: `${skippedProgress}%`,
                                        }}
                                    />
                                </div>

                                {/* Display imported, skipped, total, and remaining row counts. */}
                                <div className="lci-progress-stats">
                                    <div>
                                        <span>Imported into Database</span>
                                        <strong>
                                            {formatNumber(importedRows)}
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Skipped Contacts</span>
                                        <strong>
                                            {formatNumber(skippedRows)}
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Total CSV Records</span>
                                        <strong>
                                            {formatNumber(totalRows)}
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Remaining Records</span>
                                        <strong>
                                            {formatNumber(remainingRows)}
                                        </strong>
                                    </div>
                                </div>

                                {/* Display selected batch matching and processing details. */}
                                <div className="lci-progress-footer">
                                    <span>
                                        Matched:{" "}
                                        {formatNumber(
                                            progressBatch.matched_rows,
                                        )}
                                    </span>

                                    <span>
                                        Pending:{" "}
                                        {formatNumber(
                                            progressBatch.pending_rows,
                                        )}
                                    </span>

                                    <span>
                                        Skipped:{" "}
                                        {formatNumber(
                                            progressBatch.skipped_rows,
                                        )}
                                    </span>

                                    <span>
                                        Started:{" "}
                                        {formatDate(progressBatch.started_at)}
                                    </span>
                                </div>

                                {/* Display the processing error when a batch fails. */}
                                {progressBatch.error_message && (
                                    <div className="lci-run-error">
                                        <AlertCircle size={15} />
                                        {progressBatch.error_message}
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="lci-empty-state">
                                <Database size={28} />

                                <strong>No import batch available</strong>

                                <span>
                                    Upload a CSV file to start your first
                                    import.
                                </span>
                            </div>
                        )}
                    </section>

                    {/* Import batch history with invalid contact downloads. */}
                    <section className="lci-history-panel">
                        <div className="lci-section-heading">
                            <div>
                                <h2>
                                    <History size={18} />
                                    Import History
                                </h2>

                                <p>
                                    Select a batch to inspect its imported
                                    records or download skipped contacts.
                                </p>
                            </div>
                        </div>

                        <div className="lci-table-wrap">
                            <table className="lci-table">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Status</th>
                                        <th>Total Rows</th>
                                        <th>Matched</th>
                                        <th>Pending</th>
                                        <th>Skipped</th>
                                        <th>Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {batches.length ? (
                                        batches.map((batch) => (
                                            <tr
                                                key={batch.batch_id}
                                                className={
                                                    selectedBatch ===
                                                    batch.batch_id
                                                        ? "lci-row-selected"
                                                        : ""
                                                }
                                            >
                                                <td>
                                                    {batch.original_filename}
                                                </td>

                                                <td>
                                                    <ImportStatus
                                                        status={batch.status}
                                                    />
                                                </td>

                                                <td>
                                                    {formatNumber(
                                                        batch.total_rows,
                                                    )}
                                                </td>

                                                <td>
                                                    {formatNumber(
                                                        batch.matched_rows,
                                                    )}
                                                </td>

                                                <td>
                                                    {formatNumber(
                                                        batch.pending_rows,
                                                    )}
                                                </td>

                                                <td>
                                                    {formatNumber(
                                                        batch.skipped_rows,
                                                    )}
                                                </td>

                                                <td>
                                                    {formatDate(
                                                        batch.created_at,
                                                    )}
                                                </td>

                                                <td>
                                                    <div className="lci-history-actions">
                                                        <button
                                                            type="button"
                                                            className="lci-view-btn"
                                                            onClick={() => {
                                                                setSelectedBatch(
                                                                    batch.batch_id,
                                                                );

                                                                setSelectedBatchDetails(
                                                                    null,
                                                                );

                                                                setPage(1);
                                                            }}
                                                        >
                                                            View Records
                                                        </button>

                                                        {Number(
                                                            batch.skipped_rows ||
                                                                0,
                                                        ) > 0 &&
                                                            batch.invalid_file_path && (
                                                                <a
                                                                    className="lci-download-btn"
                                                                    href={`/admin/learner-course-import/${batch.batch_id}/invalid-contacts/download`}
                                                                    title="Download invalid contacts CSV"
                                                                >
                                                                    <Download
                                                                        size={
                                                                            14
                                                                        }
                                                                    />
                                                                    Download
                                                                    Invalid
                                                                </a>
                                                            )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan="8"
                                                className="lci-table-empty"
                                            >
                                                No import history found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {/* Imported learner records and matching filters. */}
                    <section className="lci-records-panel">
                        <div className="lci-section-heading">
                            <div>
                                <h2>
                                    <Users size={18} />
                                    Imported Learner Records
                                </h2>

                                <p>
                                    Browse matched and pending learner course
                                    records.
                                </p>
                            </div>
                        </div>

                        {/* Record search and match status filters. */}
                        <div className="lci-record-filters">
                            <select
                                value={matchStatus}
                                onChange={(event) => {
                                    setMatchStatus(event.target.value);
                                    setPage(1);
                                }}
                            >
                                <option value="all">All Records</option>
                                <option value="matched">Matched</option>
                                <option value="pending">Pending</option>
                            </select>

                            <div className="lci-search-box">
                                <Search size={15} />

                                <input
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    onKeyDown={(event) => {
                                        if (event.key === "Enter") {
                                            handleSearch();
                                        }
                                    }}
                                    placeholder="Search contact, learner or course..."
                                />
                            </div>

                            <button
                                type="button"
                                className="lci-filter-btn"
                                onClick={handleSearch}
                            >
                                Search
                            </button>
                        </div>

                        {/* Paginated learner course import records. */}
                        <div className="lci-table-wrap">
                            <table className="lci-table lci-record-table">
                                <thead>
                                    <tr>
                                        <th>Contact</th>
                                        <th>Learner</th>
                                        <th>Course</th>
                                        <th>State</th>
                                        <th>Completion</th>
                                        <th>Match Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {records.length ? (
                                        records.map((record) => (
                                            <tr key={record.id}>
                                                <td>{record.contact || "—"}</td>

                                                <td>
                                                    {record.learner
                                                        ? `${record.learner.first_name || ""} ${record.learner.last_name || ""}`.trim() ||
                                                          record.learner.name ||
                                                          "Matched learner"
                                                        : `${record.first_name || ""} ${record.last_name || ""}`.trim() ||
                                                          "—"}
                                                </td>

                                                <td>
                                                    {record.course_name || "—"}
                                                </td>

                                                <td>{record.state || "—"}</td>

                                                <td>
                                                    {record.course_completion ??
                                                        "—"}
                                                    {record.course_completion !==
                                                        null &&
                                                    record.course_completion !==
                                                        undefined
                                                        ? "%"
                                                        : ""}
                                                </td>

                                                <td>
                                                    <ImportStatus
                                                        status={
                                                            record.match_status
                                                        }
                                                    />
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan="6"
                                                className="lci-table-empty"
                                            >
                                                No learner records found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Navigate through imported learner records. */}
                        <div className="lci-pagination">
                            <span>
                                Showing {formatNumber(pagination.from || 0)}–
                                {formatNumber(pagination.to || 0)} of{" "}
                                {formatNumber(pagination.total || 0)}
                            </span>

                            <div>
                                <button
                                    type="button"
                                    disabled={!pagination.prev_page_url}
                                    onClick={() =>
                                        setPage((current) =>
                                            Math.max(1, current - 1),
                                        )
                                    }
                                >
                                    Previous
                                </button>

                                <button
                                    type="button"
                                    disabled={!pagination.next_page_url}
                                    onClick={() =>
                                        setPage((current) => current + 1)
                                    }
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                <footer className="admin-footer">
                    © {new Date().getFullYear()} YouthHub. All rights reserved.
                </footer>
            </main>
        </div>
    );
}
