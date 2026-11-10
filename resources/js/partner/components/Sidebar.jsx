import {
    LayoutDashboard,
    GraduationCap,
    CalendarDays,
    UsersRound,
    CircleUserRound,
    CircleHelp,
    LogOut,
    Download,
    Megaphone,
    Bell,
    X,
} from "lucide-react";
import { useEffect, useState } from "react";

/* =========================================
   PRIMARY NAVIGATION
========================================= */

const primary = [
    ["dashboard", "Dashboard", LayoutDashboard],
    ["learners", "Learners", GraduationCap],
    ["transactions", "Event Transaction", CalendarDays],
    ["agents", "Field Agents", UsersRound],
    ["announcements", "Announcements", Megaphone],
    ["adminAnnouncements", "Admin Announcements", Bell],
    ["downloads", "My Downloads", Download],
];

/* =========================================
   SECONDARY NAVIGATION
========================================= */

const secondary = [
    ["profile", "Profile", CircleUserRound],
    ["support", "Help & Support", CircleHelp],
];

/* =========================================
   PARTNER SIDEBAR
========================================= */

export default function Sidebar({
    activePage,
    onNavigate,
    mobileOpen,
    onClose,
    unreadCount,
    onUnreadCountChange,
}) {
    const [localUnreadCount, setLocalUnreadCount] = useState(0);

    /* Fetch the unread Admin announcement count. */
    useEffect(() => {
        let cancelled = false;

        // Load the current unread count for the authenticated Partner.
        async function loadUnreadCount() {
            try {
                const response = await fetch(
                    "/api/partner/admin-announcements/unread-count",
                    {
                        method: "GET",
                        credentials: "same-origin",
                        headers: {
                            Accept: "application/json",
                        },
                    },
                );

                if (!response.ok) return;

                const data = await response.json();
                const count = Number(data.unread_count || 0);

                if (!cancelled) {
                    setLocalUnreadCount(count);
                    onUnreadCountChange?.(count);
                }
            } catch (error) {
                console.error(
                    "Unable to load Admin announcement unread count:",
                    error,
                );
            }
        }

        loadUnreadCount();

        return () => {
            cancelled = true;
        };
    }, [activePage, onUnreadCountChange]);

    /* Use the parent count when it is supplied. */
    const displayedUnreadCount =
        unreadCount !== undefined && unreadCount !== null
            ? Number(unreadCount)
            : localUnreadCount;

    /* Render an individual sidebar navigation item. */
    const renderItem = ([key, label, Icon]) => (
        <button
            key={key}
            type="button"
            className={`yp-nav-item ${activePage === key ? "is-active" : ""}`}
            onClick={() => onNavigate(key)}
            aria-current={activePage === key ? "page" : undefined}
        >
            <Icon size={20} />
            <span>{label}</span>

            {key === "adminAnnouncements" && displayedUnreadCount > 0 && (
                <span className="yp-admin-announcement-count">
                    {displayedUnreadCount > 99 ? "99+" : displayedUnreadCount}
                </span>
            )}
        </button>
    );

    return (
        <>
            {/* MOBILE OVERLAY */}

            <div
                className={`yp-scrim ${mobileOpen ? "is-visible" : ""}`}
                onClick={onClose}
            />

            {/* SIDEBAR */}

            <aside className={`yp-sidebar ${mobileOpen ? "is-open" : ""}`}>
                {/* BRAND */}

                <div className="yp-brand">
                    <img
                        src="/icon.png"
                        alt=""
                        onError={(event) => {
                            event.currentTarget.style.display = "none";
                        }}
                    />

                    <span>
                        YouthHub <strong>Partner</strong>
                    </span>

                    <button
                        type="button"
                        className="yp-close"
                        onClick={onClose}
                        aria-label="Close menu"
                    >
                        <X size={20} />
                    </button>
                </div>

                {/* PRIMARY MENU */}

                <nav className="yp-nav">{primary.map(renderItem)}</nav>

                {/* BOTTOM MENU */}

                <div className="yp-sidebar-bottom">
                    <nav className="yp-nav">{secondary.map(renderItem)}</nav>

                    {/* LOGOUT */}

                    <form method="POST" action="/partner/logout">
                        <input
                            type="hidden"
                            name="_token"
                            value={
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                )?.content ?? ""
                            }
                        />

                        <button className="yp-nav-item yp-logout" type="submit">
                            <LogOut size={20} />
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </aside>
        </>
    );
}
