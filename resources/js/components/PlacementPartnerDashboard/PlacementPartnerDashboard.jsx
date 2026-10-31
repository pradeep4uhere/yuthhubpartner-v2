import { lazy, Suspense, useState } from "react";

import Sidebar from "./components/Sidebar.jsx";
import Header from "./components/Header.jsx";
import Overview from "./pages/Overview.jsx";

import "./PlacementPartnerDashboard.css";
import ComingSoon from "../../components/ComingSoon";
import PlacementPartnerProfile from "./pages/PlacementPartnerProfile.jsx";
import PlacementSupportTicketsPage from "./PlacementSupportTicketsPage";

// Lazy-load the Learners page to reduce the initial dashboard bundle.
const Learners = lazy(() => import("./pages/Learners.jsx"));

// Lazy-load the Event Transactions page.
const EventTransactions = lazy(
    () => import("./pages/event/EventTransactions.jsx"),
);

// Lazy-load the Associated Field Agents page.
const FieldAgents = lazy(() => import("./pages/fieldAgent/FieldAgents.jsx"));

// Lazy-load the Field Agent Details page.
const FieldAgentDetails = lazy(
    () => import("./pages/fieldAgent/FieldAgentDetails.jsx"),
);

// Define the pages available in the Placement Partner dashboard.
const pages = {
    dashboard: ["Dashboard", Overview],
    agents: ["Associated Field Agents", FieldAgents],
    "agent-details": ["Field Agent Details", FieldAgentDetails],
    learners: ["Learners", Learners],
    transactions: ["Event Transactions", EventTransactions],
    reports: ["Reports", null],
    profile: ["Profile", PlacementPartnerProfile],
    support: ["Help & Support", PlacementSupportTicketsPage],
};

/**
 * Render the Placement Partner workspace and manage page navigation.
 */
export default function PlacementPartnerDashboard({
    placementPartner = {},
    placementPartnerName = "",
    logoutUrl = "",
}) {
    // Track the currently selected dashboard page.
    const [activePage, setActivePage] = useState("dashboard");

    // Track the selected Field Agent ID for the details page.
    const [selectedAgentId, setSelectedAgentId] = useState(null);

    // Track whether the mobile sidebar is open.
    const [mobileOpen, setMobileOpen] = useState(false);

    // Normalize the authenticated Placement Partner data safely.
    const partner = placementPartner || {};

    /**
     * Navigate to a dashboard page and optionally select a Field Agent.
     */
    const navigate = (page, agentId = null) => {
        if (!pages[page]) {
            return;
        }

        if (page === "agent-details") {
            if (agentId === null || agentId === undefined) {
                return;
            }

            setSelectedAgentId(agentId);
        }

        setActivePage(page);
        setMobileOpen(false);
    };

    /**
     * Open the details page for the selected Field Agent.
     */
    const openAgentDetails = (agentId) => {
        navigate("agent-details", agentId);
    };

    /**
     * Return to the Associated Field Agents listing.
     */
    const backToAgents = () => {
        navigate("agents");
    };

    // Resolve the current page title and component.
    const [title, Page] = pages[activePage];

    // Keep the Agents navigation item active on the details page.
    const sidebarActivePage =
        activePage === "agent-details" ? "agents" : activePage;

    // Resolve the name displayed in the dashboard header.
    const displayName =
        partner.partner_placement_name ||
        partner.name ||
        placementPartnerName ||
        "Placement Partner";

    return (
        <div className="yp-shell ypp-shell">
            {/* Render the Placement Partner sidebar. */}
            <Sidebar
                activePage={sidebarActivePage}
                onNavigate={navigate}
                mobileOpen={mobileOpen}
                onClose={() => setMobileOpen(false)}
                logoutUrl={logoutUrl || partner.logoutUrl || ""}
            />

            {/* Render the main dashboard workspace. */}
            <main className="yp-main">
                <Header
                    partnerName={displayName}
                    onMenu={() => setMobileOpen(true)}
                />

                {/* Render the selected page with a loading fallback. */}
                <Suspense
                    fallback={
                        <div className="yp-content">Loading workspace…</div>
                    }
                >
                    {Page ? (
                        <Page
                            placementPartner={partner}
                            partner={partner}
                            onNavigate={navigate}
                            selectedAgentId={selectedAgentId}
                            onAgentClick={openAgentDetails}
                            onBack={backToAgents}
                        />
                    ) : (
                        <ComingSoon title={title} />
                    )}
                </Suspense>
            </main>
        </div>
    );
}
