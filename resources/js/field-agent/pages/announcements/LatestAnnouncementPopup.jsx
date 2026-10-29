import React, { useEffect, useState } from "react";
import {
    Megaphone,
    X,
    CalendarDays,
    ExternalLink,
    FileText,
} from "lucide-react";

import { PriorityBadge, formatAnnouncementDate } from "./AnnouncementsPage";

import "./LatestAnnouncementPopup.css";

const API = "/api/field-agent/announcements";

// Identify whether the attachment can be displayed as an image.
function isImageAttachment(filename = "") {
    return /\.(jpg|jpeg|png)$/i.test(filename);
}

// Display the latest unread announcement as a dashboard popup.
export default function LatestAnnouncementPopup({ onViewAll }) {
    const [announcement, setAnnouncement] = useState(null);
    const [visible, setVisible] = useState(false);
    const [loading, setLoading] = useState(false);

    // Fetch the latest unread announcement when the component mounts.
    useEffect(() => {
        const controller = new AbortController();

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

                // Prefer the most recently published unread announcement.
                const latest = [...recipients]
                    .filter((item) => item.announcement)
                    .sort(
                        (a, b) =>
                            new Date(b.announcement.published_at).getTime() -
                            new Date(a.announcement.published_at).getTime(),
                    )[0];

                if (!latest || controller.signal.aborted) return;

                const item = latest.announcement;

                // Prevent repeatedly showing a dismissed announcement.
                const dismissedKey = `fa-announcement-popup-${item.id}`;

                if (sessionStorage.getItem(dismissedKey)) {
                    return;
                }

                setAnnouncement(item);
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

    // Close the popup and remember the dismissal for this browser session.
    function closePopup() {
        if (announcement) {
            sessionStorage.setItem(
                `fa-announcement-popup-${announcement.id}`,
                "1",
            );
        }

        setVisible(false);
    }

    // Open the complete announcements page.
    function viewAllAnnouncements() {
        closePopup();
        onViewAll?.();
    }

    // Support closing the popup using the Escape key.
    useEffect(() => {
        if (!visible) return;

        function handleEscape(event) {
            if (event.key === "Escape") closePopup();
        }

        window.addEventListener("keydown", handleEscape);

        return () => window.removeEventListener("keydown", handleEscape);
    }, [visible, announcement]);

    if (!visible || !announcement || loading) return null;

    const attachmentUrl = `${API}/${announcement.id}/attachment`;

    const hasImage =
        announcement.attachment_name &&
        isImageAttachment(announcement.attachment_name);

    const hasPdf = /\.pdf$/i.test(announcement.attachment_name || "");

    return (
        <div
            className="fa-lap-overlay"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    closePopup();
                }
            }}
        >
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

                    <button
                        type="button"
                        className="fa-lap-close"
                        onClick={closePopup}
                        aria-label="Close announcement"
                    >
                        <X size={22} />
                    </button>
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
                        <a
                            className="fa-lap-image-link"
                            href={attachmentUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            title="Open full image"
                        >
                            <img
                                src={attachmentUrl}
                                alt={
                                    announcement.attachment_name ||
                                    "Announcement attachment"
                                }
                                className="fa-lap-image"
                            />

                            <span className="fa-lap-image-action">
                                <ExternalLink size={15} />
                                View Full Image
                            </span>
                        </a>
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
                </div>

                <footer className="fa-lap-footer">
                    <span>Partner Communication</span>

                    <button type="button" onClick={viewAllAnnouncements}>
                        View All Announcements
                    </button>
                </footer>
            </section>
        </div>
    );
}
