import { lazy, Suspense, useMemo, useState } from "react";

import Sidebar from "./components/Sidebar.jsx";
import Header from "./components/Header.jsx";

import "./PartnerDashboard.css";

import YouthHubLoader from "./components/loader/YouthHubLoader.jsx";
import SupportTicketsPage from "./pages/SupportTicketsPage";

/* =========================================
   LAZY LOADED PAGES
========================================= */

const Overview = lazy(() => import("./pages/Overview.jsx"));

const Learners = lazy(() => import("./pages/Learners.jsx"));

const Downloads = lazy(() => import("./pages/Downloads.jsx"));

// Lazy-load the Partner Announcement Management page.
const Announcements = lazy(
    () => import("./pages/announcements/Announcements.jsx"),
);

const Reports = lazy(() => import("./pages/Reports.jsx"));

const EventTransactions = lazy(
    () => import("./pages/event/EventTransactions.jsx"),
);

const FieldAgents = lazy(() => import("./pages/fieldAgent/FieldAgents.jsx"));

const FieldAgentDetails = lazy(
    () => import("./pages/fieldAgent/FieldAgentDetails.jsx"),
);

const Profile = lazy(() => import("./pages/ProfilePage.jsx"));

/* =========================================
   LOAD PARTNER TRANSLATIONS
========================================= */

const getPartnerTranslations = () => {
    const element = document.getElementById("partner-translations");

    if (!element) return {};

    try {
        return JSON.parse(element.textContent || "{}");
    } catch (error) {
        console.error("Unable to read partner translations:", error);
        return {};
    }
};

/* =========================================
   PAGE CONFIGURATION
========================================= */

const pages = {
    dashboard: {
        titleKey: "dashboard",
        component: Overview,
    },

    learners: {
        titleKey: "learners",
        component: Learners,
    },

    transactions: {
        titleKey: "event_transactions",
        component: EventTransactions,
    },

    agents: {
        titleKey: "field_agents",
        component: FieldAgents,
    },

    agentDetails: {
        titleKey: "field_agent_details",
        component: FieldAgentDetails,
    },

    downloads: {
        titleKey: "downloads",
        component: Downloads,
    },

    announcements: {
        titleKey: "announcements",
        component: Announcements,
    },

    profile: {
        titleKey: "profile",
        component: Profile,
    },

    support: {
        titleKey: "help_support",
        component: SupportTicketsPage,
    },
};

/* =========================================
   PARTNER DASHBOARD
========================================= */

export default function PartnerDashboard({ partner = {}, centers = [] }) {
    const partnerName = partner.name || "Partner";

    /* =====================================
       MULTILINGUAL
    ===================================== */

    const translations = useMemo(() => getPartnerTranslations(), []);

    const t = (key, fallback = null) => {
        return translations[key] ?? fallback ?? key;
    };

    /* =====================================
       STATES
    ===================================== */

    const [activePage, setActivePage] = useState("dashboard");

    const [mobileOpen, setMobileOpen] = useState(false);

    const [selectedAgentId, setSelectedAgentId] = useState(null);

    /* =====================================
       ACTIVE PAGE
    ===================================== */

    const currentPage = pages[activePage] || pages.dashboard;

    const ActivePage = currentPage.component;

    const title = t(
        currentPage.titleKey,
        activePage === "downloads" ? "My Downloads" : null,
    );

    /* =====================================
       NAVIGATION
    ===================================== */

    const navigate = (page) => {
        if (!pages[page]) return;

        setActivePage(page);
        setMobileOpen(false);
    };

    /* =====================================
       FIELD AGENT DETAILS
    ===================================== */

    const openAgentDetails = (agentId) => {
        if (agentId === null || agentId === undefined) return;

        setSelectedAgentId(agentId);
        setActivePage("agentDetails");
        setMobileOpen(false);
    };

    const backToAgents = () => {
        setSelectedAgentId(null);
        setActivePage("agents");
        setMobileOpen(false);
    };

    /* =====================================
       RENDER
    ===================================== */

    return (
        <div className="yp-shell">
            {/* SIDEBAR */}

            <Sidebar
                activePage={activePage}
                onNavigate={navigate}
                mobileOpen={mobileOpen}
                onClose={() => setMobileOpen(false)}
                t={t}
            />

            {/* MAIN CONTENT */}

            <main className="yp-main">
                {/* HEADER */}

                <Header
                    title={title}
                    partnerName={partnerName}
                    onMenu={() => setMobileOpen(true)}
                    t={t}
                />

                {/* ACTIVE PAGE */}

                <Suspense fallback={<YouthHubLoader />}>
                    <ActivePage
                        partner={partner}
                        centers={centers}
                        partnerName={partnerName}
                        t={t}
                        onNavigate={navigate}
                        onViewDetails={openAgentDetails}
                        agentId={selectedAgentId}
                        onBack={backToAgents}
                    />
                </Suspense>
            </main>
        </div>
    );
}
