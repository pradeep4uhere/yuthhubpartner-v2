
    import React, { useEffect, useState } from 'react';
    import {
        ResponsiveContainer,
        CartesianGrid,
        XAxis,
        YAxis,
        Tooltip,
        BarChart,
        Bar,
        PieChart,
        Pie,
        Cell,
        Legend,
    } from 'recharts';
    import {
        LayoutDashboard,
        Users,
        ClipboardCheck,
        ChartNoAxesCombined,
        UserRound,
        MapPin,
        Building2,
        BriefcaseBusiness,
        Mail,
        Phone,
        BadgeCheck,
        GraduationCap,
        ArrowUpRight,
        Menu,
        CalendarDays,
    } from 'lucide-react';

    import './FieldAgentDashboard.css';
    import EventActivitiesPage from './pages/EventActivitiesPage';
    import EditEventActivityPage from './pages/EditEventActivityPage';


    const getFieldAgentTranslations = () => {
        const element = document.getElementById('field-agent-translations');

        if (!element) return {};

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            console.error('Unable to read field-agent translations:', error);
            return {};
        }
    };


    const pages = {
        activities: EventActivitiesPage,
    };


    const NAV_ITEMS = [
        { id: 'overview', labelKey: 'overview', icon: LayoutDashboard },
        { id: 'learners', labelKey: 'learners', icon: Users },
        { id: 'activities', labelKey: 'activities', icon: ClipboardCheck },
        { id: 'reports', labelKey: 'reports', icon: ChartNoAxesCombined },
        { id: 'profile', labelKey: 'profile', icon: UserRound },
    ];

    function SummaryCard({ icon: Icon, label, value, description, tone }) {
        return (
            <article className={`fa-summary-card fa-summary-card--${tone}`}>
                <div className="fa-summary-card__top">
                    <span className="fa-summary-card__icon">
                        <Icon size={21} />
                    </span>
                    <ArrowUpRight size={17} className="fa-summary-card__arrow" />
                </div>

                <p className="fa-summary-card__label">{label}</p>
                <strong className="fa-summary-card__value">{value}</strong>
                <span className="fa-summary-card__description">
                    {description}
                </span>
            </article>
        );
    }

    function SectionHeading({ eyebrow, title, description }) {
        return (
            <div className="fa-section-heading">
                {eyebrow && <span>{eyebrow}</span>}
                <h2>{title}</h2>
                {description && <p>{description}</p>}
            </div>
        );
    }

    function AssignmentCard({ icon: Icon, title, name, code, tone, t }) {
        return (
            <article className="fa-assignment-card">
                <div className={`fa-assignment-card__icon fa-tone-${tone}`}>
                    <Icon size={21} />
                </div>

                <span className="fa-assignment-card__label">{title}</span>
                <h3>{name || t('not_assigned')}</h3>

                <div className="fa-assignment-card__code">
                    <span>{t('code')}</span>
                    <strong>{code || '—'}</strong>
                </div>
            </article>
        );
    }

    function ProfileItem({ icon: Icon, label, value, t }) {
        return (
            <div className="fa-profile-item">
                <span className="fa-profile-item__icon">
                    <Icon size={18} />
                </span>
                <div>
                    <span>{label}</span>
                    <strong>{value || t('not_provided')}</strong>
                </div>
            </div>
        );
    }
    export default function FieldAgentDashboard({ agent = {} }) {
        const translations = getFieldAgentTranslations();
        const t = (key) => translations[key] ?? key;
        const [activeSection, setActiveSection] = useState('overview');

        const [dashboardStats, setDashboardStats] = useState({
        my_learners: null,
        all_event_transactions: null,
        course_completed: null,
        event_verification: null,
    });

    const [statsLoading, setStatsLoading] = useState(true);
    const [statsError, setStatsError] = useState(false);

    const [chartPeriod, setChartPeriod] = useState('day');

    const [chartData, setChartData] = useState({
        activity_trend: [],
        review_status: [],
        course_completion: [],
    });

    const [chartsLoading, setChartsLoading] = useState(true);
    const [chartsError, setChartsError] = useState(false);

    const [editingActivityId, setEditingActivityId] = useState(null);
    const [successMessage, setSuccessMessage] = useState('');

    useEffect(() => {
        const controller = new AbortController();

        async function loadCharts() {
            try {
                setChartsLoading(true);
                setChartsError(false);

                const response = await fetch(
                    `/api/field-agent/dashboard-charts?period=${chartPeriod}`,
                    {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    }
                );

                if (!response.ok) {
                    throw new Error('Unable to load dashboard charts.');
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Unable to load charts.');
                }

                setChartData(result.data);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Field agent charts error:', error);
                    setChartsError(true);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setChartsLoading(false);
                }
            }
        }

        loadCharts();

        return () => controller.abort();
    }, [chartPeriod]);

    useEffect(() => {
        const controller = new AbortController();

        async function loadDashboardStats() {
            try {
                setStatsLoading(true);
                setStatsError(false);

                const response = await fetch(
                    '/api/field-agent/dashboard-stats',
                    {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    }
                );

                if (!response.ok) {
                    throw new Error('Unable to load dashboard statistics.');
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(
                        result.message || 'Unable to load dashboard statistics.'
                    );
                }

                setDashboardStats(result.data);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Dashboard stats error:', error);
                    setStatsError(true);
                }
            } finally {
                if (!controller.signal.aborted) {
                    setStatsLoading(false);
                }
            }
        }

        loadDashboardStats();

        return () => controller.abort();
    }, []);

        useEffect(() => {
            const handleNavigation = (event) => {
                const section = event.detail?.section;

                if (NAV_ITEMS.some((item) => item.id === section)) {
                    setActiveSection(section);
                }
            };

            window.addEventListener('field-agent:navigate', handleNavigation);

            return () => {
                window.removeEventListener(
                    'field-agent:navigate',
                    handleNavigation
                );
            };
        }, []);

        const activeItem =
            NAV_ITEMS.find((item) => item.id === activeSection) || NAV_ITEMS[0];

        const ActiveIcon = activeItem.icon;

    console.log('Agent profile:', agent);
    console.log('Partner:', agent?.partnerName);
    console.log('Partner Division:', agent?.centerName);

        return (
            <div className="fa-react-dashboard">


                {activeSection === 'overview' && (
                    <>
                        <div className="fa-summary-grid">
                    <SummaryCard
                            icon={Users}
                            label={t('learners')}
                            value={
                                statsLoading
                                    ? t('loading_short')
                                    : statsError
                                        ? '—'
                                        : Number(dashboardStats.my_learners ?? 0).toLocaleString('en-IN')
                            }
                            description={t('total_learner_records')}
                            tone="blue"
                        />

                        <SummaryCard
                            icon={ClipboardCheck}
                            label={t('all_events_transactions')}
                            value={
                                statsLoading
                                    ? t('loading_short')
                                    : statsError
                                        ? '—'
                                        : Number(dashboardStats.all_event_transactions ?? 0).toLocaleString('en-IN')
                            }
                            description={t('total_field_activities')}
                            tone="teal"
                        />

                            <SummaryCard
                                icon={GraduationCap}
                                label={t('course_completed')}
                                value={
                                    statsLoading
                                        ? t('loading_short')
                                        : statsError
                                            ? '—'
                                            : Number(dashboardStats.course_completed ?? 0).toLocaleString('en-IN')
                                }
                                description={t('total_course_completions')}
                                tone="violet"
                            />

                            <SummaryCard
                                icon={BadgeCheck}
                                label={t('event_verification')}
                                value={
                                    statsLoading
                                        ? t('loading_short')
                                        : statsError
                                            ? '—'
                                            : Number(dashboardStats.event_verification ?? 0).toLocaleString('en-IN')
                                }
                                description={t('accepted_verification_records')}
                                tone="orange"
                            />

                        </div>



    <section className="fa-charts-section">
        <div className="fa-charts-heading">
            <div>
                <span className="fa-charts-eyebrow">
                    {t('performance_overview')}
                </span>
                <h2>{t('my_dashboard_analytics')}</h2>
                <p>{t('track_activities_progress')}</p>
            </div>

            <div className="fa-period-filter">
                <button
                    type="button"
                    className={chartPeriod === 'day' ? 'active' : ''}
                    onClick={() => setChartPeriod('day')}
                >
                    {t('daily')}
                </button>

                <button
                    type="button"
                    className={chartPeriod === 'week' ? 'active' : ''}
                    onClick={() => setChartPeriod('week')}
                >
                    {t('weekly')}
                </button>

                <button
                    type="button"
                    className={chartPeriod === 'month' ? 'active' : ''}
                    onClick={() => setChartPeriod('month')}
                >
                    {t('monthly')}
                </button>
            </div>
        </div>

        {chartsError ? (
            <div className="fa-chart-message">
                Charts could not be loaded. Please refresh the page and try again.
            </div>
        ) : chartsLoading ? (
            <div className="fa-chart-message">Loading dashboard analytics...</div>
        ) : (
            <div className="fa-charts-grid">
                <article className="fa-chart-card fa-chart-card--wide">
                    <div className="fa-chart-card__heading">
                        <div>
                            <h3>{t('event_activity_trend')}</h3>
                            <p>{t('transactions_over_time')}</p>
                        </div>
                        <span className="fa-chart-icon">
                            <ChartNoAxesCombined size={20} />
                        </span>
                    </div>

                    <div className="fa-chart-area">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart
                                data={chartData.activity_trend}
                                margin={{ top: 12, right: 8, left: -18, bottom: 4 }}
                            >
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis
                                    dataKey="label"
                                    tick={{ fontSize: 12 }}
                                    axisLine={false}
                                    tickLine={false}
                                />
                                <YAxis
                                    allowDecimals={false}
                                    tick={{ fontSize: 12 }}
                                    axisLine={false}
                                    tickLine={false}
                                />
                                <Tooltip />
                                <Bar
                                    dataKey="total"
                                    name={t('transactions')}
                                    fill="#4f46e5"
                                    radius={[5, 5, 0, 0]}
                                    maxBarSize={42}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </article>

                <article className="fa-chart-card">
                    <div className="fa-chart-card__heading">
                        <div>
                            <h3>{t('event_review_status')}</h3>
                            <p>{t('review_breakdown')}</p>
                        </div>
                        <span className="fa-chart-icon fa-chart-icon--teal">
                            <ClipboardCheck size={20} />
                        </span>
                    </div>

                    <div className="fa-chart-area fa-chart-area--donut">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={chartData.review_status}
                                    dataKey="value"
                                    nameKey="name"
                                    innerRadius={58}
                                    outerRadius={88}
                                    paddingAngle={3}
                                    cx="50%"
                                    cy="45%"
                                >
                                    {chartData.review_status.map((entry) => {
                                        const colors = {
                                            Open: '#64748b',
                                            Pending: '#f59e0b',
                                            Accepted: '#10b981',
                                            Rejected: '#ef4444',
                                            Return: '#8b5cf6',
                                        };

                                        return (
                                            <Cell
                                                key={entry.name}
                                                fill={colors[entry.name] || '#94a3b8'}
                                            />
                                        );
                                    })}
                                </Pie>
                                <Tooltip />
                                <Legend
                                    verticalAlign="bottom"
                                    iconType="circle"
                                    wrapperStyle={{ fontSize: '12px' }}
                                    formatter={(value) => {
                                        const item = chartData.review_status.find(
                                            (status) => status.name === value
                                        );

                                        return `${value} (${item?.value ?? 0})`;
                                    }}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </article>

                <article className="fa-chart-card">
                    <div className="fa-chart-card__heading">
                        <div>
                            <h3>{t('course_completion')}</h3>
                            <p>{t('center_learner_completion')}</p>
                        </div>
                        <span className="fa-chart-icon fa-chart-icon--violet">
                            <GraduationCap size={20} />
                        </span>
                    </div>

                    <div className="fa-chart-area">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart
                                data={chartData.course_completion}
                                margin={{ top: 18, right: 8, left: -18, bottom: 4 }}
                            >
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis
                                    dataKey="name"
                                    tick={{ fontSize: 12 }}
                                    axisLine={false}
                                    tickLine={false}
                                />
                                <YAxis
                                    allowDecimals={false}
                                    tick={{ fontSize: 12 }}
                                    axisLine={false}
                                    tickLine={false}
                                />
                                <Tooltip />
                                <Bar
                                    dataKey="value"
                                    name={t('learners')}
                                    radius={[5, 5, 0, 0]}
                                    maxBarSize={58}
                                >
                                    {chartData.course_completion.map((entry) => (
                                        <Cell
                                            key={entry.name}
                                            fill={
                                                entry.name === 'Completed'
                                                    ? '#10b981'
                                                    : '#cbd5e1'
                                            }
                                        />
                                    ))}
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </article>
            </div>
        )}
    </section>

                        <section className="fa-content-panel">
                            <SectionHeading
                                eyebrow="ACCOUNT INFORMATION"
                                title="My details"
                                description="Your registered field-agent profile."
                            />

                            <div className="fa-profile-grid">
                                 <ProfileItem
                                    icon={UserRound}
                                    label="Agent Name"
                                    value={agent.name}
                                />

                                 <ProfileItem
                                    icon={UserRound}
                                    label="Sakhi ID"
                                    value={agent.sakhiId}
                                />
                                <ProfileItem
                                    icon={Building2}
                                    label="Partner"
                                    value={agent.partnerName+ '[ '+ agent.partnerCode + ']'}
                                />
                                <ProfileItem
                                    icon={MapPin}
                                    label="Partner Division"
                                    value={agent.centerName +' [ '+agent.centerCode +' ] '}
                                />
                                <ProfileItem
                                    icon={Mail}
                                    label="Email address"
                                    value={agent.email}
                                />
                                <ProfileItem
                                    icon={Phone}
                                    label="Contact number"
                                    value={agent.contactNumber}
                                />
                                <ProfileItem
                                    icon={BadgeCheck}
                                    label="CSC ID"
                                    value={agent.cscId}
                                />

                                <ProfileItem
                                    icon={MapPin}
                                    label="State"
                                    value={agent.state}
                                />
                                <ProfileItem
                                    icon={MapPin}
                                    label="District"
                                    value={agent.district}
                                />


                            </div>
                        </section>
                    </>
                )}



                {activeSection !== 'overview' && (
                    editingActivityId !== null ? (
                        <EditEventActivityPage
                            activityId={editingActivityId}
                            t={t}
                             onBack={(message) => {
                                if (message) {
                                    setSuccessMessage(message);
                                }

                            }}

                        />
                    ) : pages[activeSection] ? (
                        React.createElement(pages[activeSection], {
                            agent,
                            onNavigate: setActiveSection,
                            t,
                            ...(activeSection === 'activities' && {
                                onEditActivity: (activity) => {
                                    setEditingActivityId(activity.id);
                                },
                            }),
                        })
                    ) : (
                        <div className="dashboard-placeholder">
                            <h2>{t(activeItem.labelKey)}</h2>
                            <p>{t('module_ready_message')}</p>

                            <button
                                type="button"
                                onClick={() => setActiveSection('overview')}
                            >
                                {t('back_to_overview')}
                            </button>
                        </div>
                    )
                )}

            </div>
        );
    }
