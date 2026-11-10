import React, { useState } from "react";
import { Megaphone, Building2 } from "lucide-react";

import AnnouncementsPage from "./AnnouncementsPage.jsx";
import AdminAnnouncementsPage from "./AdminAnnouncementsPage.jsx";
import "./AnnouncementsHub.css";

export default function AnnouncementsHubPage({
    onUnreadChange,
    onAdminUnreadChange,
    adminUnreadCount = 0,
    agent,
    t,
}) {
    const [activeTab, setActiveTab] = useState("admin");

    return (
        <div className="fa-announcement-hub">
            <div className="fa-announcement-tabs">
                <button
                    type="button"
                    className={
                        activeTab === "admin"
                            ? "fa-announcement-tab active"
                            : "fa-announcement-tab"
                    }
                    onClick={() => setActiveTab("admin")}
                >
                    <Megaphone size={17} />
                    Admin Announcements
                </button>

                {adminUnreadCount > 0 && (
                    <span className="fa-announcement-count">
                        {adminUnreadCount > 99 ? "99+" : adminUnreadCount}
                    </span>
                )}

                <button
                    type="button"
                    className={
                        activeTab === "partner"
                            ? "fa-announcement-tab active"
                            : "fa-announcement-tab"
                    }
                    onClick={() => setActiveTab("partner")}
                >
                    <Building2 size={17} />
                    Partner Announcements
                </button>
            </div>

            {activeTab === "admin" ? (
                <AdminAnnouncementsPage
                    agent={agent}
                    onUnreadChange={onAdminUnreadChange}
                />
            ) : (
                <AnnouncementsPage
                    agent={agent}
                    t={t}
                    onUnreadChange={onUnreadChange}
                />
            )}
        </div>
    );
}
