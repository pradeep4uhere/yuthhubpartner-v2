import { lazy, Suspense, useEffect, useRef, useState } from "react";
import {
    BriefcaseBusiness,
    GraduationCap,
    Handshake,
    UsersRound,
} from "lucide-react";

import MetricCard from "../components/AdminDashboard/MetricCard";
import Sidebar from "../components/AdminDashboard/Sidebar";
import DashboardHeader from "../components/AdminDashboard/DashboardHeader";

// Load large dashboard sections only when they are needed.
const RecentLearners = lazy(
    () => import("../components/AdminDashboard/RecentLearners"),
);
const LatestEvents = lazy(
    () => import("../components/AdminDashboard/LatestEvents"),
);
const LearnerOutcomes = lazy(
    () => import("../components/AdminDashboard/LearnerOutcomes"),
);
const EventTransactionPieChart = lazy(
    () => import("../components/AdminDashboard/EventTransactionPieChart"),
);
const LearnerGenderPieChart = lazy(
    () => import("../components/AdminDashboard/LearnerGenderPieChart"),
);
const LearnerAgePieChart = lazy(
    () => import("../components/AdminDashboard/LearnerAgePieChart"),
);
const EventTransactionBarChart = lazy(
    () => import("../components/AdminDashboard/EventTransactionBarChart"),
);
const LearnersByProgrammeChart = lazy(
    () => import("../components/AdminDashboard/LearnersByProgrammeChart"),
);
const Top10FieldAgentsChart = lazy(
    () => import("../components/AdminDashboard/Top10FieldAgentsChart"),
);
const EventReviewPerformanceChart = lazy(
    () => import("../components/AdminDashboard/EventReviewPerformanceChart"),
);

/**
 * Renders a shimmer placeholder while a dashboard chart is loading.
 */
function ChartSkeleton({ height = 310 }) {
    return (
        <div
            className="admin-panel admin-chart-skeleton"
            style={{ minHeight: height }}
        >
            <div className="admin-skeleton admin-skeleton--title" />
            <div className="admin-skeleton admin-skeleton--subtitle" />
            <div className="admin-chart-skeleton__body">
                <div className="admin-skeleton admin-skeleton--chart" />
                <div className="admin-chart-skeleton__legend">
                    <div className="admin-skeleton admin-skeleton--line" />
                    <div className="admin-skeleton admin-skeleton--line" />
                    <div className="admin-skeleton admin-skeleton--line" />
                </div>
            </div>
        </div>
    );
}

/**
 * Waits until a dashboard section approaches the viewport before mounting it.
 * This defers both the component render and its lazy-loaded JavaScript chunk.
 */
function LazyChart({ children, height = 310 }) {
    const containerRef = useRef(null);
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const element = containerRef.current;

        if (!element) {
            return undefined;
        }

        if (typeof IntersectionObserver === "undefined") {
            setIsVisible(true);
            return undefined;
        }

        // Observe the section and load it shortly before it enters the viewport.
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    setIsVisible(true);
                    observer.disconnect();
                }
            },
            { rootMargin: "260px 0px", threshold: 0.01 },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    return (
        <div className="admin-lazy-chart" ref={containerRef}>
            {isVisible ? (
                <Suspense fallback={<ChartSkeleton height={height} />}>
                    {children}
                </Suspense>
            ) : (
                <ChartSkeleton height={height} />
            )}
        </div>
    );
}

/**
 * Displays skeleton metric cards until the dashboard summary API responds.
 */
function MetricSkeleton() {
    return (
        <div className="admin-metric admin-metric-skeleton" aria-hidden="true">
            <div className="admin-metric__top">
                <div className="admin-skeleton admin-skeleton--icon" />
                <div className="admin-skeleton admin-skeleton--tiny" />
            </div>
            <div className="admin-skeleton admin-skeleton--metric-label" />
            <div className="admin-skeleton admin-skeleton--metric-value" />
            <div className="admin-skeleton admin-skeleton--metric-detail" />
        </div>
    );
}

/**
 * Displays the admin analytics dashboard and its supporting data sections.
 */
export default function AdminDashboard({
    adminName = "Admin",
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
    canViewSupportTickets = false,
}) {
    const [dashboardData, setDashboardData] = useState(null);
    const [dashboardLoading, setDashboardLoading] = useState(true);
    const [dashboardError, setDashboardError] = useState("");
    const [notice, setNotice] = useState("");
    const [monthlyGenderData, setMonthlyGenderData] = useState([]);
    const [genderLoading, setGenderLoading] = useState(true);

    /**
     * Shows a temporary success or information message in the dashboard.
     */
    const showNotice = (message) => {
        setNotice(message);
        window.setTimeout(() => setNotice(""), 2600);
    };

    /**
     * Returns a greeting based on the visitor's local time.
     */
    const getGreeting = () => {
        const hour = new Date().getHours();

        if (hour < 12) return "Good Morning";
        if (hour < 17) return "Good Afternoon";
        return "Good Evening";
    };

    useEffect(() => {
        let isMounted = true;

        /**
         * Fetches the monthly gender statistics used by the outcomes chart.
         */
        const fetchMonthlyGenderData = async () => {
            try {
                const response = await fetch(
                    "/admin/dashboard/monthly-gender?period=last_60_months",
                    {
                        headers: { Accept: "application/json" },
                        credentials: "same-origin",
                    },
                );

                if (!response.ok) {
                    throw new Error(
                        "Failed to load monthly gender statistics.",
                    );
                }

                const result = await response.json();

                if (isMounted) {
                    setMonthlyGenderData(result.data ?? []);
                }
            } catch (error) {
                console.error(error);
                if (isMounted) setMonthlyGenderData([]);
            } finally {
                if (isMounted) setGenderLoading(false);
            }
        };

        fetchMonthlyGenderData();

        return () => {
            isMounted = false;
        };
    }, []);

    useEffect(() => {
        let isMounted = true;

        /**
         * Fetches the dashboard summary and refreshes it every five minutes.
         */
        const fetchDashboardData = async () => {
            try {
                const response = await fetch("/admin/dashboard-data", {
                    method: "GET",
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                });

                if (!response.ok) {
                    throw new Error("Unable to load dashboard data.");
                }

                const result = await response.json();

                if (isMounted && result.success) {
                    setDashboardData(result);
                    setDashboardError("");
                }
            } catch (error) {
                if (isMounted) setDashboardError(error.message);
            } finally {
                if (isMounted) setDashboardLoading(false);
            }
        };

        fetchDashboardData();

        const intervalId = window.setInterval(
            fetchDashboardData,
            5 * 60 * 1000,
        );

        return () => {
            isMounted = false;
            window.clearInterval(intervalId);
        };
    }, []);

    const metrics = dashboardData?.metrics ?? {};

    return (
        <div className="admin-shell" id="overview">
            <Sidebar
                adminName={adminName}
                canManageAdmins={canManageAdmins}
                canViewPartners={canViewPartners}
                canViewPartnerCenters={canViewPartnerCenters}
                canManagePlacementUsers={canManagePlacementUsers}
                canViewFieldAgents={canViewFieldAgents}
                canViewEventTypes={canViewEventTypes}
                canViewEventCategories={canViewEventCategories}
                canViewEventMasters={canViewEventMasters}
                canViewDownloads={canViewDownloads}
                canViewErrorLogs={canViewErrorLogs}
                canViewSupportTickets={canViewSupportTickets}
            />

            <main className="admin-main">
                <DashboardHeader adminName={adminName} />

                <div className="admin-content">
                    {dashboardError && (
                        <p className="admin-dashboard-error" role="alert">
                            {dashboardError}
                        </p>
                    )}

                    <section className="admin-page-heading">
                        <div>
                            <p className="admin-eyebrow">
                                ADMIN OVERVIEW <span>·</span>{" "}
                                {new Date()
                                    .toLocaleDateString("en-IN", {
                                        weekday: "long",
                                        day: "2-digit",
                                        month: "long",
                                        year: "numeric",
                                    })
                                    .toUpperCase()}
                            </p>

                            <h1>
                                {getGreeting()}, {adminName} <span>✳</span>
                            </h1>

                            <p className="admin-page-heading__sub">
                                Here’s what’s happening across your programmes.
                            </p>
                        </div>
                    </section>

                    <section className="admin-metrics">
                        {dashboardLoading ? (
                            <>
                                <MetricSkeleton />
                                <MetricSkeleton />
                                <MetricSkeleton />
                                <MetricSkeleton />
                            </>
                        ) : (
                            <>
                                <MetricCard
                                    label="Learners Registered"
                                    value={
                                        metrics.learners?.toLocaleString(
                                            "en-IN",
                                        ) ?? "—"
                                    }
                                    icon={UsersRound}
                                    tone="green"
                                    detail="Total registered learners"
                                />

                                <MetricCard
                                    label="Field Agents"
                                    value={
                                        metrics.field_agents != null
                                            ? Number(
                                                  metrics.field_agents,
                                              ).toLocaleString("en-IN")
                                            : "—"
                                    }
                                    icon={GraduationCap}
                                    tone="blue"
                                    detail="Total registered field agents"
                                />

                                <MetricCard
                                    label="Partners"
                                    value={
                                        metrics.partners != null
                                            ? Number(
                                                  metrics.partners,
                                              ).toLocaleString("en-IN")
                                            : "—"
                                    }
                                    icon={Handshake}
                                    tone="yellow"
                                    detail="Total registered partners"
                                />

                                <MetricCard
                                    label="Event Transactions"
                                    value={
                                        metrics.event_transactions != null
                                            ? Number(
                                                  metrics.event_transactions,
                                              ).toLocaleString("en-IN")
                                            : "—"
                                    }
                                    icon={BriefcaseBusiness}
                                    tone="coral"
                                    detail="Total event transactions"
                                />
                            </>
                        )}
                    </section>

                    <section className="admin-insights">
                        <LazyChart height={340}>
                            <Top10FieldAgentsChart />
                        </LazyChart>

                        <LazyChart height={340}>
                            <LearnerGenderPieChart />
                        </LazyChart>

                        <LazyChart height={340}>
                            <LearnerOutcomes
                                monthlyGenderData={monthlyGenderData}
                                loading={genderLoading}
                            />
                        </LazyChart>

                        <LazyChart height={340}>
                            <LearnerAgePieChart />
                        </LazyChart>

                        <LazyChart height={340}>
                            <EventReviewPerformanceChart />
                        </LazyChart>

                        <LazyChart height={340}>
                            <LearnersByProgrammeChart />
                        </LazyChart>

                        <LazyChart height={340}>
                            <EventTransactionBarChart />
                        </LazyChart>

                        <LazyChart height={340}>
                            <EventTransactionPieChart />
                        </LazyChart>
                    </section>

                    <section className="admin-lower-grid">
                        <div className="admin-lazy-lower">
                            <Suspense fallback={<ChartSkeleton height={300} />}>
                                <RecentLearners
                                    learners={
                                        dashboardData?.recent_learners ?? []
                                    }
                                    showNotice={showNotice}
                                />
                            </Suspense>
                        </div>

                        <div className="admin-lazy-lower">
                            <Suspense fallback={<ChartSkeleton height={300} />}>
                                <LatestEvents
                                    events={
                                        dashboardData?.upcoming_events ?? []
                                    }
                                    loading={dashboardLoading}
                                />
                            </Suspense>
                        </div>
                    </section>

                    <footer className="admin-footer">
                        <span>© 2026 YouthHub Partner</span>
                        <span>All data synced</span>
                    </footer>
                </div>
            </main>

            {notice && (
                <div className="admin-toast" role="status">
                    {notice}
                    <button
                        onClick={() => setNotice("")}
                        aria-label="Dismiss notification"
                    >
                        ×
                    </button>
                </div>
            )}
        </div>
    );
}
