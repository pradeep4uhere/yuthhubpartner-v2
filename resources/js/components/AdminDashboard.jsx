
import { useEffect, useState } from 'react';
import {
    BriefcaseBusiness,
    ClipboardCheck,
    GraduationCap,
    UsersRound,
    CalendarDays,
    Filter,
    ChevronDown,
    Handshake
} from 'lucide-react';

import MetricCard from '../components/AdminDashboard/MetricCard';
import RecentLearners from '../components/AdminDashboard/RecentLearners';
import LatestEvents from '../components/AdminDashboard/LatestEvents';

// In components ko apni respective files se import karein
import Sidebar from '../components/AdminDashboard/Sidebar';
import DashboardHeader from '../components/AdminDashboard/DashboardHeader';
import LearnerOutcomes from '../components/AdminDashboard/LearnerOutcomes';
import LearningPipeline from '../components/AdminDashboard/LearningPipeline';
import EventTransactionPieChart from '../components/AdminDashboard/EventTransactionPieChart';
import LearnerGenderPieChart from '../components/AdminDashboard/LearnerGenderPieChart';
import LearnerAgePieChart from '../components/AdminDashboard/LearnerAgePieChart';
import EventTransactionBarChart from '../components/AdminDashboard/EventTransactionBarChart';
import LearnersByProgrammeChart from '../components/AdminDashboard/LearnersByProgrammeChart';
import Top10FieldAgentsChart from '../components/AdminDashboard/Top10FieldAgentsChart';
import EventReviewPerformanceChart from '../components/AdminDashboard/EventReviewPerformanceChart';

export default function AdminDashboard({
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
    const [dashboardData, setDashboardData] = useState(null);
    const [dashboardLoading, setDashboardLoading] = useState(true);
    const [dashboardError, setDashboardError] = useState('');
    const [notice, setNotice] = useState('');

    const showNotice = (message) => {
        setNotice(message);
        window.setTimeout(() => setNotice(''), 2600);
    };

     const getGreeting = () => {
        const hour = new Date().getHours();

        if (hour < 12) {
            return "Good Morning";
        } else if (hour < 17) {
            return "Good Afternoon";
        } else {
            return "Good Evening";
        }
    };

    const [region, setRegion] = useState('All regions');
    const [period, setPeriod] = useState('Last 6 months');
    const [monthlyGenderData, setMonthlyGenderData] = useState([]);
    const [genderLoading, setGenderLoading] = useState(true);

    useEffect(() => {
        fetch('/admin/dashboard/monthly-gender?period=last_60_months', {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to load monthly gender statistics.');
                }

                return response.json();
            })
            .then((result) => {
                setMonthlyGenderData(result.data ?? []);
            })
            .catch((error) => {
                console.error(error);
                setMonthlyGenderData([]);
            })
            .finally(() => {
                setGenderLoading(false);
            });
    }, []);


    useEffect(() => {
        let isMounted = true;

        const fetchDashboardData = async () => {
            try {
                const response = await fetch('/admin/dashboard-data', {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Unable to load dashboard data.');
                }

                const result = await response.json();

                if (isMounted && result.success) {
                    setDashboardData(result);
                    setDashboardError('');
                }
            } catch (error) {
                if (isMounted) {
                    setDashboardError(error.message);
                }
            } finally {
                if (isMounted) {
                    setDashboardLoading(false);
                }
            }
        };

        fetchDashboardData();

        const intervalId = window.setInterval(
            fetchDashboardData,
            5 * 60 * 1000
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
            />

            <main className="admin-main">
                <DashboardHeader adminName={adminName} />
              

                <div className="admin-content">
                    {dashboardError && (
                        <p role="alert">{dashboardError}</p>
                    )}


<section className="admin-page-heading">
    <div>
        <p className="admin-eyebrow">
            PARTNER OVERVIEW <span>·</span> SATURDAY, 26 SEPTEMBER 2026
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

                        

                        <MetricCard
                            label="Learners Registered"
                            value={metrics.learners?.toLocaleString('en-IN') ?? '—'}
                            icon={UsersRound}
                            tone="green"
                            detail="Total registered learners"
                        />

                        <MetricCard
                            label="Field Agents"
                            value={metrics.field_agents != null
                                ? Number(metrics.field_agents).toLocaleString('en-IN')
                                : dashboardLoading ? 'Loading...' : '—'}
                            icon={GraduationCap}
                            tone="blue"
                            detail="Total registered field agents"
                        />

                        <MetricCard
                            label="Partners"
                            value={metrics.partners != null
                                ? Number(metrics.partners).toLocaleString('en-IN')
                                : dashboardLoading ? 'Loading...' : '—'}
                            icon={Handshake}
                            tone="yellow"
                            detail="Total registered partners"
                        />

                        <MetricCard
                            label="Event Transactions"
                            value={metrics.event_transactions != null
                                ? Number(metrics.event_transactions).toLocaleString('en-IN')
                                : dashboardLoading ? 'Loading...' : '—'}
                            icon={BriefcaseBusiness}
                            tone="coral"
                            detail="Total event transactions"
                        />
                    </section>

                    <section className="admin-insights">
                         <Top10FieldAgentsChart/>
                        <LearnerGenderPieChart />
                        <LearnerOutcomes monthlyGenderData={monthlyGenderData} loading={genderLoading}/>
                        <LearnerAgePieChart />
                        <EventReviewPerformanceChart/>
                        <LearnersByProgrammeChart/>
                        <EventTransactionBarChart />
                        <EventTransactionPieChart />
                       
                    </section>

                    <section className="admin-lower-grid">
                        <RecentLearners
                            learners={dashboardData?.recent_learners ?? []}
                            showNotice={showNotice}
                        />

                        <LatestEvents
                            events={dashboardData?.upcoming_events ?? []}
                            loading={dashboardLoading}
                        />
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
                    <button onClick={() => setNotice('')} aria-label="Dismiss notification">
                        ×
                    </button>
                </div>
            )}
        </div>
    );
}
