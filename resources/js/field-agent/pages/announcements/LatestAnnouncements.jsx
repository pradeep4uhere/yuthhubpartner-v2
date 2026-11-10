import React, { useEffect, useState } from "react";
import { Megaphone, ArrowRight, Clock3 } from "lucide-react";

import { PriorityBadge, formatAnnouncementDate } from "./AnnouncementsPage.jsx";

import "./Announcements.css";

// Display the three latest announcements on the Field Agent overview.
export default function LatestAnnouncements({ onViewAll, refreshKey = 0 }) {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);

    // Fetch the latest announcements whenever the widget is refreshed.
    useEffect(() => {
        const controller = new AbortController();

        // Load the announcement preview from the authenticated Field Agent API.
        async function loadLatest() {
            try {
                setLoading(true);

                const response = await fetch(
                    "/api/field-agent/announcements?page=1",
                    {
                        credentials: "same-origin",
                        headers: {
                            Accept: "application/json",
                        },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error("Unable to load announcements.");
                }

                const result = await response.json();

                // Keep only valid announcement records and show the latest three.
                const announcements = (result.announcements?.data || [])
                    .filter((item) => item.announcement)
                    .sort(
                        (a, b) =>
                            new Date(b.announcement.published_at).getTime() -
                            new Date(a.announcement.published_at).getTime(),
                    )
                    .slice(0, 3);

                if (!controller.signal.aborted) {
                    setItems(announcements);
                }
            } catch (error) {
                if (error.name !== "AbortError") {
                    console.error("Latest announcements error:", error);
                    setItems([]);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }

        loadLatest();

        return () => controller.abort();
    }, [refreshKey]);

    // Hide the complete widget while loading or when no announcements exist.
    if (loading || items.length === 0) {
        return null;
    }

    return (
        <section className="fa-ann-widget">
            <div className="fa-ann-widget-head">
                <div className="fa-ann-widget-heading">
                    <span className="fa-ann-widget-icon">
                        <Megaphone size={21} />
                    </span>

                    <div>
                        <h2>Latest Announcements</h2>
                        <p>Important updates from your Partner</p>
                    </div>
                </div>

                <button type="button" onClick={onViewAll}>
                    View All <ArrowRight size={16} />
                </button>
            </div>

            <div className="fa-ann-widget-list">
                {items.map((item) => (
                    <button
                        type="button"
                        key={item.id}
                        className={`fa-ann-widget-item ${
                            !item.read ? "is-unread" : ""
                        }`}
                        onClick={onViewAll}
                    >
                        <div>
                            <strong>{item.announcement.title}</strong>

                            <p>{item.announcement.message}</p>

                            <span>
                                <Clock3 size={12} />
                                {formatAnnouncementDate(
                                    item.announcement.published_at,
                                )}
                            </span>
                        </div>

                        <div className="fa-ann-widget-tags">
                            <PriorityBadge
                                priority={item.announcement.priority}
                            />

                            {!item.read && <i className="fa-ann-new-dot" />}
                        </div>
                    </button>
                ))}
            </div>
        </section>
    );
}
