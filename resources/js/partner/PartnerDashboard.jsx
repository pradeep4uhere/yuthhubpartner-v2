import { lazy, Suspense, useState } from "react";
import Sidebar from "./components/Sidebar.jsx";
import Header from "./components/Header.jsx";
import "./PartnerDashboard.css";
import YouthHubLoader from "../partner/components/loader/YouthHubLoader.jsx";

const Overview = lazy(() => import("./pages/Overview.jsx"));
const Learners = lazy(() => import("./pages/Learners.jsx"));
const EventTransactions = lazy(
    () => import("./pages/event/EventTransactions.jsx"),
);
const FieldAgents = lazy(() => import("./pages/fieldAgent/FieldAgents.jsx"));

// NEW: Field Agent Details Page
const FieldAgentDetails = lazy(
    () => import("./pages/fieldAgent/FieldAgentDetails.jsx"),
);

const Reports = lazy(() => import("./pages/Reports.jsx"));
const Profile = lazy(() => import("./pages/Profile.jsx"));
const HelpSupport = lazy(() => import("./pages/HelpSupport.jsx"));
const pages = {
    dashboard: ["Dashboard", Overview],
    learners: ["Learners", Learners],
    transactions: ["Event Transaction", EventTransactions],
    agents: ["Field Agents", FieldAgents],

    // NEW
    agentDetails: ["Field Agent Details", FieldAgentDetails],

    reports: ["Report", Reports],
    profile: ["Profile", Profile],
    support: ["Help & Support", HelpSupport],
};
export default function PartnerDashboard({ partnerName = "Partner" }) {
    const [activePage, setActivePage] = useState("dashboard");
    const [mobileOpen, setMobileOpen] = useState(false);

    // Store selected field agent ID
    const [selectedAgentId, setSelectedAgentId] = useState(null);

    const [title, ActivePage] = pages[activePage] || pages.dashboard;
    const navigate = (page) => {
        setActivePage(page);
        setMobileOpen(false);
    };

    // Open selected agent details
    const openAgentDetails = (agentId) => {
        setSelectedAgentId(agentId);
        setActivePage("agentDetails");
        setMobileOpen(false);
    };

    // Back to field agent listing
    const backToAgents = () => {
        setActivePage("agents");
    };

    return (
        <div className="yp-shell">
            <Sidebar
                activePage={activePage}
                onNavigate={navigate}
                mobileOpen={mobileOpen}
                onClose={() => setMobileOpen(false)}
            />
            <main className="yp-main">
                <Header
                    title={title}
                    partnerName={partnerName}
                    onMenu={() => setMobileOpen(true)}
                />
                <Suspense fallback={<YouthHubLoader />}>
                    <ActivePage
                        partnerName={partnerName}
                        onNavigate={navigate}
                        // Field Agent navigation
                        onViewDetails={openAgentDetails}
                        agentId={selectedAgentId}
                        onBack={backToAgents}
                    />
                </Suspense>
            </main>
        </div>
    );
}
