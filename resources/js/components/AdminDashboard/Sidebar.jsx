
import { useState } from 'react';

import {
    Bell,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    ChevronDown,
    ChevronRight,
    CircleHelp,
    Download,
    FileCheck2,
    FileWarning,
    FolderTree,
    Handshake,
    LayoutDashboard,
    LocateFixed,
    MoreHorizontal,
    ShieldCheck,
    UsersRound,
} from 'lucide-react';

const navigation = [
    { label: 'Dashboard', icon: LayoutDashboard, href: '#overview' },
    { label: 'Learners', icon: UsersRound, href: '/admin/learners' },
    { label: 'Event Transactions', icon: FileCheck2, href: '/admin/event-transactions' },
    { label: 'Log History', icon: FileWarning, href: '/admin/log-history' },
];

export default function Sidebar({
    adminName = 'Admin',
    canManageAdmins = false,
    canViewPartners = false,
    canViewPartnerCenters = false,
    canManagePlacementUsers = false,
    canViewFieldAgents = false,
    canViewEventTypes = false,
    canViewEventCategories = false,
    canViewEventMasters = false,
    canViewDownloads = false,
    canViewErrorLogs = false,
}) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [activeSection, setActiveSection] = useState('Dashboard');

    const adminInitials = adminName
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

    const extraLinks = [
        {
            show: canManageAdmins,
            label: 'Admin users',
            href: '/admin/users',
            icon: ShieldCheck,
        },
        {
            show: canViewPartners,
            label: 'Partners',
            href: '/admin/partners',
            icon: Handshake,
        },
        {
            show: canViewPartnerCenters,
            label: 'Partner centers',
            href: '/admin/partner-centers',
            icon: Building2,
        },
        {
            show: canManagePlacementUsers,
            label: 'Placement users',
            href: '/admin/partner-placement-users',
            icon: BriefcaseBusiness,
        },
        {
            show: canViewFieldAgents,
            label: 'Field agents',
            href: '/admin/field-agents',
            icon: LocateFixed,
        },
        {
            show: canViewEventTypes,
            label: 'Event Type',
            href: '/admin/event-types',
            icon: CalendarDays,
        },
        {
            show: canViewEventCategories,
            label: 'Event Category',
            href: '/admin/event-categories',
            icon: FolderTree,
        },
        {
            show: canViewEventMasters,
            label: 'Event Master',
            href: '/admin/event-masters',
            icon: CalendarDays,
        },
        {
            show: canViewDownloads,
            label: 'Downloads',
            href: '/admin/downloads',
            icon: Download,
        },
        {
            show: canViewErrorLogs,
            label: 'All Error Logs',
            href: '/admin/log-history',
            icon: FileWarning,
        },
    ];

    return (
        <>
            <aside
                className={`admin-sidebar ${
                    mobileMenuOpen ? 'admin-sidebar--open' : ''
                }`}
            >
                <a
                    className="admin-brand"
                    href="/"
                    aria-label="YouthHub Partner home"
                >
                    <span className="brand__mark" aria-hidden="true">
                        <img src="/icon.png" alt="YouthHub Partner" />
                    </span>

                    <span className="admin-brand__name">
                        YouthHub <b>Partner</b>
                    </span>
                </a>

                <div className="admin-org-switcher">
                    <span className="admin-org-switcher__badge">A</span>

                    <span className="admin-org-switcher__copy">
                        <b>Administrator</b>
                        <small>Dashboard</small>
                    </span>

                    <ChevronDown size={15} />
                </div>

                <p className="admin-nav-label">WORKSPACE</p>

                <nav className="admin-nav" aria-label="Admin navigation">
                    {[...navigation, ...extraLinks.filter((link) => link.show)]
                        .map(({ label, icon: Icon, href }) => (
                            <a
                                className={`admin-nav__link ${
                                    activeSection === label ? 'is-active' : ''
                                }`}
                                href={href}
                                key={label}
                                onClick={() => {
                                    setActiveSection(label);
                                    setMobileMenuOpen(false);
                                }}
                            >
                                <Icon size={18} strokeWidth={1.8} />
                                <span>{label}</span>
                            </a>
                        ))}
                </nav>

                <div className="admin-sidebar__spacer" />

                <div className="admin-support">
                    <span className="admin-support__icon">
                        <CircleHelp size={18} />
                    </span>

                    <div>
                        <b>Need a hand?</b>
                        <span>Visit the partner help centre</span>
                    </div>

                    <ChevronRight size={16} />
                </div>

                <button
                    className="admin-profile"
                    onClick={() => {
                        window.alert('Profile settings are not connected yet.');
                    }}
                >
                    <span className="admin-profile__avatar">
                        {adminInitials}
                    </span>

                    <span className="admin-profile__copy">
                        <b>{adminName}</b>
                        <small>Administrator</small>
                    </span>

                    <MoreHorizontal size={18} />
                </button>
            </aside>

            {mobileMenuOpen && (
                <button
                    className="admin-backdrop"
                    aria-label="Close navigation"
                    onClick={() => setMobileMenuOpen(false)}
                />
            )}
        </>
    );
}
