import { useMemo, useState } from 'react';
import {
    ArrowDownToLine,
    ArrowDownRight,
    ArrowUpRight,
    Bell,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    ChartNoAxesCombined,
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    CircleHelp,
    ClipboardCheck,
    FileCheck2,
    Filter,
    GraduationCap,
    Handshake,
    LayoutDashboard,
    LocateFixed,
    Menu,
    MoreHorizontal,
    Search,
    Settings2,
    ShieldCheck,
    UsersRound,
    X,
    FolderTree,
    Download
} from 'lucide-react';

const learners = [
    { name: 'Aarushi Kumari', initials: 'AK', location: 'Gaya, Bihar', program: 'Digital marketing', stage: 'In training', color: 'coral', date: '26 Sep 2026' },
    { name: 'Meena Devi', initials: 'MD', location: 'Jaipur, Rajasthan', program: 'Retail operations', stage: 'Interview ready', color: 'blue', date: '25 Sep 2026' },
    { name: 'Sana Parveen', initials: 'SP', location: 'Bhopal, Madhya Pradesh', program: 'Data entry & office', stage: 'Onboarded', color: 'green', date: '25 Sep 2026' },
    { name: 'Kavita Sahu', initials: 'KS', location: 'Raipur, Chhattisgarh', program: 'Customer support', stage: 'Placed', color: 'yellow', date: '24 Sep 2026' },
    { name: 'Rukhsar Khan', initials: 'RK', location: 'Lucknow, Uttar Pradesh', program: 'Digital marketing', stage: 'Documents pending', color: 'violet', date: '23 Sep 2026' },
];

const navigation = [
    { label: 'Dashbaord', icon: LayoutDashboard, href: '#overview' },
    { label: 'Learners', icon: UsersRound, href: '/admin/learners' },
    { label: 'Event Transactions', icon: FileCheck2, href: '/admin/event-transactions' },
];

const monthlyOutcomes = [
    { month: 'Apr', reached: 42, placed: 23 },
    { month: 'May', reached: 55, placed: 31 },
    { month: 'Jun', reached: 47, placed: 28 },
    { month: 'Jul', reached: 68, placed: 42 },
    { month: 'Aug', reached: 61, placed: 36 },
    { month: 'Sep', reached: 82, placed: 54 },
];

const stageStyles = {
    'In training': 'stage--amber',
    'Interview ready': 'stage--blue',
    Onboarded: 'stage--green',
    Placed: 'stage--teal',
    'Documents pending': 'stage--rose',
};

function exportLearners(rows) {
    const columns = ['Name', 'Location', 'Program', 'Stage', 'Updated'];
    const values = rows.map((learner) => [learner.name, learner.location, learner.program, learner.stage, learner.date]);
    const csv = [columns, ...values]
        .map((row) => row.map((value) => `"${value.replaceAll('"', '""')}"`).join(','))
        .join('\r\n');
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');

    link.href = url;
    link.download = 'youthhub-learners.csv';
    link.click();
    URL.revokeObjectURL(url);
}

function MetricCard({ label, value, change, direction, icon: Icon, tone, detail }) {
    const TrendIcon = direction === 'down' ? ArrowDownRight : ArrowUpRight;

    return (
        <article className="admin-metric">
            <div className="admin-metric__top">
                <span className={`admin-metric__icon admin-metric__icon--${tone}`}><Icon size={18} strokeWidth={1.8} /></span>
                <button className="admin-icon-button admin-metric__more" aria-label={`${label} options`} title="More options"><MoreHorizontal size={18} /></button>
            </div>
            <p className="admin-metric__label">{label}</p>
            <div className="admin-metric__value-row">
                <strong>{value}</strong>
                <span className={`admin-trend admin-trend--${direction}`}><TrendIcon size={14} />{change}</span>
            </div>
            <p className="admin-metric__detail">{detail}</p>
        </article>
    );
}

export default function AdminDashboard({ adminName = 'Admin',
    canManageAdmins = false,
    canViewPartners = false,
    canViewPartnerCenters = false,
    canManagePlacementUsers = false,
    canViewFieldAgents = false,
    canViewEventTypes = false,
    canViewEventCategories = false,
    canViewEventMasters = false,
    canViewDownloads = false,
    }) {

    console.log({
        canViewEventTypes,
        canViewEventCategories,
        canViewEventMasters,
    });
    const [activeSection, setActiveSection] = useState('Overview');
    const [query, setQuery] = useState('');
    const [period, setPeriod] = useState('Last 6 months');
    const [region, setRegion] = useState('All regions');
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [notice, setNotice] = useState('');
    const adminInitials = adminName.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
    const filteredLearners = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();

        return learners.filter((learner) =>
            [learner.name, learner.location, learner.program, learner.stage]
                .some((value) => value.toLowerCase().includes(normalizedQuery)),
        );
    }, [query]);

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

    const showNotice = (message) => {
        setNotice(message);
        window.setTimeout(() => setNotice(''), 2600);
    };



    return (
        <div className="admin-shell" id="overview">
            <aside className={`admin-sidebar ${mobileMenuOpen ? 'admin-sidebar--open' : ''}`}>
                <a className="admin-brand" href="/" aria-label="YouthHub Partner home">
                     <span className="brand__mark" aria-hidden="true">
                        <img src="icon.png" alt="YouthHub Partner" />
                    </span>
                    <span className="admin-brand__name">YouthHub <b>Partner</b></span>
                </a>

                <div className="admin-org-switcher">
                    <span className="admin-org-switcher__badge">A</span>
                    <span className="admin-org-switcher__copy"><b>Administrator</b><small>Dashboard</small></span>
                    <ChevronDown  size={15} />
                </div>

                <p className="admin-nav-label">WORKSPACE</p>
                <nav className="admin-nav" aria-label="Admin navigation">
                    {navigation.map(({ label, icon: Icon, href, count }) => (
                        <a
                            className={`admin-nav__link ${activeSection === label ? 'is-active' : ''}`}
                            href={href}
                            key={label}
                            onClick={() => {
                                setActiveSection(label);
                                setMobileMenuOpen(false);
                            }}
                        >
                            <Icon size={18} strokeWidth={1.8} />
                            <span>{label}</span>
                            {count && <small>{count}</small>}
                        </a>
                    ))}
                    {canManageAdmins && (
                        <a className="admin-nav__link" href="/admin/users">
                            <ShieldCheck color='RED' size={18} strokeWidth={1.8} />
                            <span>Admin users</span>
                        </a>
                    )}

                    {canViewPartners && (
                        <a className="admin-nav__link" href="/admin/partners">
                            <Handshake color='Green' size={18} strokeWidth={1.8} />
                            <span>Partners</span>
                        </a>
                    )}
                    {canViewPartnerCenters && (
                        <a className="admin-nav__link" href="/admin/partner-centers">
                            <Building2 color='Green' size={18} strokeWidth={1.8} />
                            <span>Partner centers</span>
                        </a>
                    )}
                    {canManagePlacementUsers && (
                        <a className="admin-nav__link" href="/admin/partner-placement-users">
                            <BriefcaseBusiness color='skyblue' size={18} strokeWidth={1.8} />
                            <span>Placement users</span>
                        </a>
                    )}
                    {canViewFieldAgents && (
                        <a className="admin-nav__link" href="/admin/field-agents">
                            <LocateFixed color='orange'  size={18} strokeWidth={1.8} />
                            <span>Field agents</span>
                        </a>
                    )}

                    {canViewEventTypes && (
                        <a className="admin-nav__link" href="/admin/event-types">
                            <CalendarDays  color='green'  size={18} strokeWidth={1.8} />
                            <span>Event Type</span>
                        </a>
                    )}

                    {canViewEventCategories && (
                        <a className="admin-nav__link" href="/admin/event-categories">
                            <FolderTree color='green'  size={18} strokeWidth={1.8} />
                            <span>Event Category</span>
                        </a>
                    )}

                    {canViewEventMasters && (
                        <a className="admin-nav__link" href="/admin/event-masters">
                            <CalendarDays color='green'  size={18} strokeWidth={1.8} />
                            <span>Event Master</span>
                        </a>
                    )}


                    {canViewDownloads && (
                        <a className="admin-nav__link" href="/admin/downloads">
                            <Download color='brown'  size={18} strokeWidth={1.8} />
                            <span>Downloads</span>
                        </a>
                    )}



                </nav>

                <div className="admin-sidebar__spacer" />
                <div className="admin-support">
                    <span className="admin-support__icon"><CircleHelp size={18} /></span>
                    <div><b>Need a hand?</b><span>Visit the partner help centre</span></div>
                    <ChevronRight size={16} />
                </div>
                <button className="admin-profile" onClick={() => showNotice('Profile settings are not connected yet.')}>
                    <span className="admin-profile__avatar">{adminInitials}</span>
                    <span className="admin-profile__copy"><b>{adminName}</b><small>Administrator</small></span>
                    <MoreHorizontal size={18} />
                </button>
            </aside>

            {mobileMenuOpen && <button className="admin-backdrop" aria-label="Close navigation" onClick={() => setMobileMenuOpen(false)} />}

            <main className="admin-main">
                <header className="admin-topbar">
                    <button className="admin-icon-button admin-menu-toggle" aria-label="Open navigation" onClick={() => setMobileMenuOpen(true)}><Menu size={20} /></button>
                    <div className="admin-breadcrumb"><span>Admin Workspace</span><ChevronRight size={14} /><b>{activeSection}</b></div>
                    <div className="admin-topbar__actions">
                        <span className="admin-demo-label"><span /> Notification</span>
                        <button className="admin-icon-button admin-notification" aria-label="Notifications" title="Notifications" onClick={() => showNotice('You are all caught up.') }><Bell size={18} /><i /></button>
                        <form method="POST" action="/admin/logout" className="admin-logout-form">
                            <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content ?? ''} />
                            <button className="admin-topbar__settings" type="submit">Sign out</button>
                        </form>
                        <button className="admin-topbar__settings" onClick={() => showNotice('Workspace settings are not connected yet.')}><Settings2 size={16} /><span>Settings</span></button>
                    </div>
                </header>

                <div className="admin-content">
                    <section className="admin-page-heading">
                        <div>
                            <p className="admin-eyebrow">PARTNER OVERVIEW <span>·</span> SATURDAY, 26 SEPTEMBER 2026</p>
                            <h1>  {getGreeting()}, {adminName} <span>✳</span></h1>
                            <p className="admin-page-heading__sub">Here’s what’s happening across your programmes.</p>
                        </div>
                        <div className="admin-filters">
                            <label className="admin-select-wrap"><Filter size={15} /><select value={region} onChange={(event) => setRegion(event.target.value)} aria-label="Filter by region"><option>All regions</option><option>North region</option><option>Central region</option><option>East region</option></select><ChevronDown size={14} /></label>
                            <label className="admin-select-wrap"><CalendarDays size={15} /><select value={period} onChange={(event) => setPeriod(event.target.value)} aria-label="Select reporting period"><option>Last 6 months</option><option>This month</option><option>This quarter</option><option>This year</option></select><ChevronDown size={14} /></label>
                        </div>
                    </section>

                    <section className="admin-metrics" aria-label="Programme summary">
                        <MetricCard label="Learners Registred" value="1,284" change="12.8%" direction="up" icon={UsersRound} tone="green" detail="vs previous 6 months" />
                        <MetricCard label="Field Agents" value="846" change="8.4%" direction="up" icon={GraduationCap} tone="blue" detail="66% of learners reached" />
                        <MetricCard label="Partners" value="512" change="6.2%" direction="up" icon={ClipboardCheck} tone="yellow" detail="vs previous 6 months" />
                        <MetricCard label="Event Transactions" value="238" change="2.1%" direction="down" icon={BriefcaseBusiness} tone="coral" detail="placement conversion 46%" />
                    </section>

                    <section className="admin-insights">
                        <article className="admin-panel admin-outcomes" id="placements">
                            <div className="admin-panel__heading">
                                <div><h2>Learner outcomes</h2><p>Mobilised and placed learners over time</p></div>
                                <button className="admin-period-button" onClick={() => showNotice(`Showing ${period.toLowerCase()} for ${region.toLowerCase()}.`)}>{period}<ChevronDown size={14} /></button>
                            </div>
                            <div className="admin-chart-legend"><span><i className="legend-dot legend-dot--reached" />Learners reached</span><span><i className="legend-dot legend-dot--placed" />Placed</span></div>
                            <div className="admin-chart" role="img" aria-label="Bar chart showing learner reach and placements from April to September">
                                <div className="admin-chart__axis"><span>400</span><span>300</span><span>200</span><span>100</span><span>0</span></div>
                                <div className="admin-chart__plot">
                                    {[400, 300, 200, 100, 0].map((tick) => <div className="admin-chart__gridline" style={{ bottom: `${tick / 4}%` }} key={tick} />)}
                                    <div className="admin-chart__columns">
                                        {monthlyOutcomes.map(({ month, reached, placed }) => (
                                            <div className="admin-chart__month" key={month}>
                                                <div className="admin-chart__bars"><span className="admin-chart__bar admin-chart__bar--reached" style={{ height: `${reached}%` }} /><span className="admin-chart__bar admin-chart__bar--placed" style={{ height: `${placed}%` }} /></div>
                                                <span>{month}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                            <div className="admin-outcomes__footer"><span><ChartNoAxesCombined size={16} /> <b>+18%</b> learner reach this period</span><button onClick={() => showNotice('Detailed outcomes report is coming soon.')}>View report <ArrowUpRight size={14} /></button></div>
                        </article>

                        <article className="admin-panel admin-pipeline" id="learning">
                            <div className="admin-panel__heading"><div><h2>Learning pipeline</h2><p>1,284 learners across all stages</p></div><button className="admin-icon-button" aria-label="Pipeline options" title="Pipeline options" onClick={() => showNotice('Pipeline options are not connected yet.')}><MoreHorizontal size={19} /></button></div>
                            <div className="admin-pipeline__list">
                                <div className="admin-pipeline__row"><span className="pipeline-marker pipeline-marker--indigo" /><span className="admin-pipeline__label">Mobilised</span><b>1,284</b><span className="admin-pipeline__percent">100%</span><div className="admin-pipeline__track"><i style={{ width: '100%' }} /></div></div>
                                <div className="admin-pipeline__row"><span className="pipeline-marker pipeline-marker--blue" /><span className="admin-pipeline__label">Onboarded</span><b>1,046</b><span className="admin-pipeline__percent">81%</span><div className="admin-pipeline__track"><i style={{ width: '81%' }} /></div></div>
                                <div className="admin-pipeline__row"><span className="pipeline-marker pipeline-marker--amber" /><span className="admin-pipeline__label">In training</span><b>846</b><span className="admin-pipeline__percent">66%</span><div className="admin-pipeline__track"><i style={{ width: '66%' }} /></div></div>
                                <div className="admin-pipeline__row"><span className="pipeline-marker pipeline-marker--green" /><span className="admin-pipeline__label">Certified</span><b>512</b><span className="admin-pipeline__percent">40%</span><div className="admin-pipeline__track"><i style={{ width: '40%' }} /></div></div>
                                <div className="admin-pipeline__row"><span className="pipeline-marker pipeline-marker--coral" /><span className="admin-pipeline__label">Placed</span><b>238</b><span className="admin-pipeline__percent">19%</span><div className="admin-pipeline__track"><i style={{ width: '19%' }} /></div></div>
                            </div>
                            <button className="admin-text-link" onClick={() => showNotice('Full learner pipeline view is coming soon.')}>Explore learner pipeline <ArrowUpRight size={14} /></button>
                        </article>
                    </section>

                    <section className="admin-lower-grid">
                        <article className="admin-panel admin-learners" id="learners">
                            <div className="admin-panel__heading admin-learners__heading">
                                <div><h2>Recently updated learners</h2><p>Keep track of the latest learner activity</p></div>
                                <div className="admin-table-actions">
                                    <label className="admin-search"><Search size={15} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search learners" aria-label="Search learners" />{query && <button onClick={() => setQuery('')} aria-label="Clear search"><X size={13} /></button>}</label>
                                    <button className="admin-export" onClick={() => exportLearners(filteredLearners)}><ArrowDownToLine size={15} /><span>Export</span></button>
                                </div>
                            </div>
                            <div className="admin-table-scroll">
                                <table className="admin-table">
                                    <thead><tr><th>Learner</th><th>Programme</th><th>Current stage</th><th>Last updated</th><th><span className="visually-hidden">Actions</span></th></tr></thead>
                                    <tbody>
                                        {filteredLearners.map((learner) => (
                                            <tr key={learner.name}>
                                                <td><div className="learner-cell"><span className={`learner-avatar learner-avatar--${learner.color}`}>{learner.initials}</span><span><b>{learner.name}</b><small>{learner.location}</small></span></div></td>
                                                <td>{learner.program}</td>
                                                <td><span className={`admin-stage ${stageStyles[learner.stage]}`}><i />{learner.stage}</span></td>
                                                <td className="admin-table__date">{learner.date}</td>
                                                <td><button className="admin-icon-button admin-row-more" aria-label={`More actions for ${learner.name}`} onClick={() => showNotice(`${learner.name}'s profile is not connected yet.`)}><MoreHorizontal size={18} /></button></td>
                                            </tr>
                                        ))}
                                        {filteredLearners.length === 0 && <tr><td className="admin-empty" colSpan="5">No learners match “{query}”.</td></tr>}
                                    </tbody>
                                </table>
                            </div>
                            <div className="admin-table-footer"><span>Showing <b>{filteredLearners.length}</b> of <b>1,284</b> learners</span><div><button className="admin-icon-button" aria-label="Previous page" disabled><ChevronLeft size={17} /></button><span className="admin-page-count">1 / 129</span><button className="admin-icon-button" aria-label="Next page" onClick={() => showNotice('Pagination will be connected to learner records.')}><ChevronRight size={17} /></button></div></div>
                        </article>

                        <article className="admin-panel admin-events" id="events">
                            <div className="admin-panel__heading"><div><h2>Upcoming events</h2><p>Next on your programme calendar</p></div><button className="admin-icon-button" aria-label="More event options" title="More options"><MoreHorizontal size={19} /></button></div>
                            <div className="admin-event-list">
                                <div className="admin-event"><div className="admin-event__date"><b>29</b><span>SEP</span></div><div className="admin-event__copy"><b>Digital skills orientation</b><span><CalendarDays size={13} /> Tue, 10:00 AM <i /> Patna, Bihar</span><small><UsersRound size={13} /> 42 learners registered</small></div></div>
                                <div className="admin-event"><div className="admin-event__date admin-event__date--orange"><b>02</b><span>OCT</span></div><div className="admin-event__copy"><b>Career readiness workshop</b><span><CalendarDays size={13} /> Fri, 11:30 AM <i /> Jaipur, Rajasthan</span><small><UsersRound size={13} /> 28 learners registered</small></div></div>
                                <div className="admin-event"><div className="admin-event__date admin-event__date--blue"><b>06</b><span>OCT</span></div><div className="admin-event__copy"><b>Employer connect day</b><span><CalendarDays size={13} /> Tue, 09:30 AM <i /> Bhopal, Madhya Pradesh</span><small><UsersRound size={13} /> 64 learners registered</small></div></div>
                            </div>
                            <button className="admin-text-link" onClick={() => showNotice('Programme calendar is coming soon.')}>View programme calendar <ArrowUpRight size={14} /></button>
                        </article>
                    </section>

                    <footer className="admin-footer"><span>© 2026 YouthHub Partner <i /> Digital Girls Hub</span><span><Check size={13} /> All data synced just now</span></footer>
                </div>
            </main>

            {notice && <div className="admin-toast" role="status"><Check size={16} />{notice}<button onClick={() => setNotice('')} aria-label="Dismiss notification"><X size={14} /></button></div>}
        </div>
    );
}
