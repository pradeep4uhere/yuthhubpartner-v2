import {
    LayoutDashboard,
    GraduationCap,
    CalendarDays,
    UsersRound,
    ChartNoAxesColumnIncreasing,
    CircleUserRound,
    CircleHelp,
    LogOut,
    Download,
    X,
} from "lucide-react";

/* =========================================
   PRIMARY NAVIGATION
========================================= */

const primary = [
    ["dashboard", "Dashboard", LayoutDashboard],
    ["learners", "Learners", GraduationCap],
    ["transactions", "Event Transaction", CalendarDays],
    ["agents", "Field Agents", UsersRound],

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
}) {
    const item = ([key, label, Icon]) => (
        <button
            key={key}
            type="button"
            className={`yp-nav-item ${activePage === key ? "is-active" : ""}`}
            onClick={() => onNavigate(key)}
            aria-current={activePage === key ? "page" : undefined}
        >
            <Icon size={22} />
            <span>{label}</span>
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
                        onError={(e) => {
                            e.currentTarget.style.display = "none";
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

                <nav className="yp-nav">{primary.map(item)}</nav>

                {/* BOTTOM MENU */}

                <div className="yp-sidebar-bottom">
                    <nav className="yp-nav">{secondary.map(item)}</nav>

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
                            <LogOut size={22} />
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </aside>
        </>
    );
}
