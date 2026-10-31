import {
    LayoutDashboard,
    Building2,
    GraduationCap,
    CalendarDays,
    UsersRound,
    ChartNoAxesColumnIncreasing,
    CircleUserRound,
    CircleHelp,
    LogOut,
    X,
} from "lucide-react";
const primary = [
    ["dashboard", "Dashboard", LayoutDashboard],
    ["partners", "Associated Partners", Building2],
    ["learners", "Learners", GraduationCap],
    ["transactions", "Event Transactions", CalendarDays],
    ["agents", "Field Agents", UsersRound],
    ["reports", "Reports", ChartNoAxesColumnIncreasing],
];
const secondary = [
    ["profile", "Profile", CircleUserRound],
    ["support", "Help & Support", CircleHelp],
];
export default function Sidebar({
    activePage,
    onNavigate,
    mobileOpen,
    onClose,
    logoutUrl,
}) {
    const item = ([key, label, Icon]) => (
        <button
            key={key}
            type="button"
            className={`yp-nav-item ${activePage === key ? "is-active" : ""}`}
            onClick={() => onNavigate(key)}
            aria-current={activePage === key ? "page" : undefined}
        >
            <Icon size={20} />
            <span>{label}</span>
        </button>
    );
    return (
        <>
            <div
                className={`yp-scrim ${mobileOpen ? "is-visible" : ""}`}
                onClick={onClose}
            />
            <aside className={`yp-sidebar ${mobileOpen ? "is-open" : ""}`}>
                <div className="yp-brand">
                    <img src="/icon.png" alt="" />
                    <span>
                        YouthHub <strong>Placement</strong>
                    </span>
                    <button
                        className="yp-close"
                        type="button"
                        onClick={onClose}
                        aria-label="Close menu"
                    >
                        <X size={20} />
                    </button>
                </div>
                <nav className="yp-nav">{primary.map(item)}</nav>
                <div className="yp-sidebar-bottom">
                    <nav className="yp-nav">{secondary.map(item)}</nav>
                    <form method="POST" action={logoutUrl}>
                        <input
                            type="hidden"
                            name="_token"
                            value={
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                )?.content ?? ""
                            }
                        />
                        <button
                            className="yp-nav-item yp-logout"
                            type="submit"
                            disabled={!logoutUrl}
                        >
                            <LogOut size={20} />
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </aside>
        </>
    );
}
