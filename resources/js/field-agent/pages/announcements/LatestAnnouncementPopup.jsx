import React, { useEffect, useState } from "react";
import {
    Megaphone,
    CalendarDays,
    ExternalLink,
    FileText,
    CheckCircle2,
    Loader2,
} from "lucide-react";

import { PriorityBadge, formatAnnouncementDate } from "./AnnouncementsPage";

import "./LatestAnnouncementPopup.css";

const API = "/api/field-agent/announcements";

// Identify whether the attachment can be displayed as an image.
function isImageAttachment(filename = "") {
    return /\.(jpg|jpeg|png)$/i.test(filename);
}

// Display the latest unread announcement until it is marked as read.
export default function LatestAnnouncementPopup({ onViewAll, onMarkedRead }) {
    const [announcement, setAnnouncement] = useState(null);
    const [visible, setVisible] = useState(false);
    const [loading, setLoading] = useState(false);
    const [markingRead, setMarkingRead] = useState(false);
    const [readError, setReadError] = useState("");
    const [imagePreviewUrl, setImagePreviewUrl] = useState("");
    const [imagePreviewError, setImagePreviewError] = useState("");

    // Fetch the latest unread announcement when the component mounts.
    useEffect(() => {
        const controller = new AbortController();

        // Load the latest unread announcement from the Field Agent API.
        async function loadLatestAnnouncement() {
            try {
                setLoading(true);

                const response = await fetch(`${API}?status=unread&page=1`, {
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                    },
                    signal: controller.signal,
                });

                if (!response.ok) return;

                const result = await response.json();
                const recipients = result.announcements?.data || [];

                // Select the newest published announcement from unread records.
                const latest = [...recipients]
                    .filter((item) => item.announcement)
                    .sort(
                        (a, b) =>
                            new Date(b.announcement.published_at).getTime() -
                            new Date(a.announcement.published_at).getTime(),
                    )[0];

                if (!latest || controller.signal.aborted) return;

                setAnnouncement(latest.announcement);
                setVisible(true);
            } catch (error) {
                if (error.name !== "AbortError") {
                    console.error("Latest announcement popup error:", error);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }

        loadLatestAnnouncement();

        return () => controller.abort();
    }, []);

    // Fetch and validate the private announcement image before displaying it.
    useEffect(() => {
        if (!announcement?.attachment_name) return;

        const filename = announcement.attachment_name;

        if (!/\.(jpg|jpeg|png)$/i.test(filename)) return;

        let cancelled = false;
        let objectUrl = null;

        // Load the image through the authenticated Field Agent API.
        async function loadAnnouncementImage() {
            try {
                setImagePreviewUrl("");
                setImagePreviewError("");

                const response = await fetch(
                    `${API}/${announcement.id}/attachment`,
                    {
                        method: "GET",
                        credentials: "same-origin",
                        headers: {
                            Accept: "image/*",
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error(
                        `Image API returned HTTP ${response.status}`,
                    );
                }

                const contentType = response.headers.get("content-type") || "";

                if (!contentType.startsWith("image/")) {
                    throw new Error(`Invalid image response: ${contentType}`);
                }

                const blob = await response.blob();
                const bytes = new Uint8Array(await blob.arrayBuffer());

                // Validate the PNG file signature before creating its preview URL.
                if (/\.png$/i.test(filename)) {
                    const pngSignature = [137, 80, 78, 71, 13, 10, 26, 10];

                    const isValidPng = pngSignature.every(
                        (byte, index) => bytes[index] === byte,
                    );

                    if (!isValidPng) {
                        console.error(
                            "Invalid PNG response signature:",
                            Array.from(bytes.slice(0, 16)),
                        );

                        throw new Error(
                            "The API returned corrupted PNG image data.",
                        );
                    }
                }

                objectUrl = URL.createObjectURL(blob);

                if (!cancelled) {
                    setImagePreviewUrl(objectUrl);
                } else {
                    URL.revokeObjectURL(objectUrl);
                }
            } catch (error) {
                console.error("Announcement image preview error:", error);

                if (!cancelled) {
                    setImagePreviewError(error.message);
                }
            }
        }

        loadAnnouncementImage();

        return () => {
            cancelled = true;

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
        };
    }, [announcement]);

    // Mark the announcement as read and close the popup only after API success.
    async function markAnnouncementAsRead() {
        if (!announcement || markingRead) return;

        try {
            setMarkingRead(true);
            setReadError("");

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            const response = await fetch(`${API}/${announcement.id}/read`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
                },
            });

            const result = await response.json();

            if (!response.ok || result.success !== true) {
                throw new Error(
                    result.message ||
                        "Unable to mark this announcement as read.",
                );
            }

            setVisible(false);
            setAnnouncement(null);

            // Refresh the dashboard unread count after the read is confirmed.
            onMarkedRead?.();
        } catch (error) {
            console.error("Mark announcement as read error:", error);
            setReadError(
                error.message || "Unable to mark this announcement as read.",
            );
        } finally {
            setMarkingRead(false);
        }
    }

    // Navigate to the complete announcements page without dismissing the popup.
    function viewAllAnnouncements() {
        onViewAll?.();
    }

    if (!visible || !announcement || loading) return null;

    const attachmentUrl = `${API}/${announcement.id}/attachment`;

    const hasImage =
        announcement.attachment_name &&
        isImageAttachment(announcement.attachment_name);

    const hasPdf = /\.pdf$/i.test(announcement.attachment_name || "");

    return (
        <div className="fa-lap-overlay">
            <section
                className="fa-lap-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="fa-lap-title"
            >
                <header className="fa-lap-header">
                    <div className="fa-lap-heading">
                        <Megaphone size={22} />
                        <h2 id="fa-lap-title">Announcement</h2>
                    </div>
                </header>

                <div className="fa-lap-body">
                    <div className="fa-lap-meta">
                        <PriorityBadge priority={announcement.priority} />

                        <span>
                            <CalendarDays size={14} />
                            {formatAnnouncementDate(announcement.published_at)}
                        </span>
                    </div>

                    <h3 className="fa-lap-title">{announcement.title}</h3>

                    <div className="fa-lap-message">{announcement.message}</div>

                    {hasImage && (
                        <div className="fa-lap-image-wrapper">
                            {imagePreviewUrl ? (
                                <a
                                    className="fa-lap-image-link"
                                    href={imagePreviewUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="Open full image"
                                >
                                    <img
                                        src={imagePreviewUrl}
                                        alt={announcement.attachment_name}
                                        className="fa-lap-image"
                                    />

                                    <span className="fa-lap-image-action">
                                        <ExternalLink size={15} />
                                        View Full Image
                                    </span>
                                </a>
                            ) : imagePreviewError ? (
                                <p className="fa-lap-error" role="alert">
                                    Image preview failed: {imagePreviewError}
                                </p>
                            ) : (
                                <p>Loading announcement image...</p>
                            )}
                        </div>
                    )}

                    {hasPdf && (
                        <a
                            className="fa-lap-pdf"
                            href={attachmentUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <FileText size={20} />
                            <span>{announcement.attachment_name}</span>
                            <ExternalLink size={16} />
                        </a>
                    )}

                    {readError && (
                        <p className="fa-lap-error" role="alert">
                            {readError}
                        </p>
                    )}
                </div>

                <footer className="fa-lap-footer">
                    <span>Partner Communication</span>

                    <div className="fa-lap-actions">
                        <button
                            type="button"
                            className="fa-lap-view-all"
                            onClick={viewAllAnnouncements}
                        >
                            View All Announcements
                        </button>

                        <button
                            type="button"
                            className="fa-lap-mark-read"
                            onClick={markAnnouncementAsRead}
                            disabled={markingRead}
                        >
                            {markingRead ? (
                                <>
                                    <Loader2
                                        size={16}
                                        className="fa-lap-spinner"
                                    />
                                    Marking...
                                </>
                            ) : (
                                <>
                                    <CheckCircle2 size={16} />
                                    Mark as Read
                                </>
                            )}
                        </button>
                    </div>
                </footer>
            </section>
        </div>
    );
}
