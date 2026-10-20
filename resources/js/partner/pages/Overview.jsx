import { useEffect, useState } from "react";
import {
    GraduationCap,
    UsersRound,
    CalendarDays,
    ChartNoAxesCombined,
    ChartPie,
    Clock3,
    Building2,
    ArrowRight,
    Target,
    Leaf,
    Activity,
} from "lucide-react";
import {
    AreaChart,
    Area,
    CartesianGrid,
    XAxis,
    YAxis,
    Tooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
} from "recharts";
import StatCard from "../components/StatCard.jsx";
import { getDashboardSummary } from "../services/partnerApi.js";
import RecentActivities from "../../partner/pages/RecentActivities.jsx";
import Top10FieldAgentsChart from "../../partner/pages/Top10FieldAgentsChart";
import LearnerAgePieChart from "../../partner/pages/LearnerAgePieChart";
import EventTransactionPieChart from "../../partner/pages/EventTransactionPieChart";
const fmt = (v) =>
    typeof v === "number" ? new Intl.NumberFormat("en-IN").format(v) : "—";
const colors = ["#0877f8", "#08b384", "#ffb41f", "#8e46ef"];
export default function Overview({ partnerName, onNavigate }) {
    const [data, setData] = useState(null),
        [error, setError] = useState(""),
        [retry, setRetry] = useState(0);

    const [growth, setGrowth] = useState([]);
    const [growthPeriod, setGrowthPeriod] = useState(6);
    const [growthLoading, setGrowthLoading] = useState(true);
    const [growthError, setGrowthError] = useState("");

    // Stores transaction types fetched from the dedicated API.
    const [types, setTypes] = useState([]);
    // Tracks the transaction breakdown API loading state.
    const [typesLoading, setTypesLoading] = useState(true);
    // Stores an error message if the transaction API request fails.
    const [typesError, setTypesError] = useState("");

    /**
     * Fetches the event transaction breakdown from the Laravel API
     * when the dashboard component mounts.
     */
    useEffect(() => {
        const controller = new AbortController();

        /**
         * Retrieves transaction counts grouped by transaction type.
         */
        const fetchTransactionTypes = async () => {
            setTypesLoading(true);
            setTypesError("");

            try {
                const response = await fetch(
                    "/api/partner/dashboard/event-transactions-by-type",
                    {
                        method: "GET",
                        credentials: "same-origin",
                        signal: controller.signal,
                        headers: {
                            Accept: "application/json",
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error("Failed to fetch event transaction types");
                }

                const data = await response.json();

                setTypes(data.types ?? []);
            } catch (error) {
                if (error.name === "AbortError") return;

                console.error("Transaction Types API Error:", error);

                setTypes([]);
                setTypesError("Unable to load transaction breakdown.");
            } finally {
                if (!controller.signal.aborted) {
                    setTypesLoading(false);
                }
            }
        };

        fetchTransactionTypes();

        return () => controller.abort();
    }, []);

    useEffect(() => {
        const controller = new AbortController();

        const fetchGrowth = async () => {
            setGrowthLoading(true);
            setGrowthError("");

            try {
                const response = await fetch(
                    `/api/partner/dashboard/growth-overview?months=${growthPeriod}`,
                    {
                        method: "GET",
                        credentials: "same-origin",
                        signal: controller.signal,
                        headers: {
                            Accept: "application/json",
                        },
                    },
                );

                if (!response.ok) {
                    throw new Error("Failed to fetch growth data");
                }

                const data = await response.json();

                setGrowth(data.growth ?? []);
            } catch (error) {
                if (error.name === "AbortError") return;

                console.error("Growth API Error:", error);

                setGrowth([]);
                setGrowthError("Unable to load growth analytics.");
            } finally {
                if (!controller.signal.aborted) {
                    setGrowthLoading(false);
                }
            }
        };

        fetchGrowth();

        return () => controller.abort();
    }, [growthPeriod]);

    useEffect(() => {
        const c = new AbortController();
        setData(null);
        setError("");
        getDashboardSummary({ signal: c.signal })
            .then(setData)
            .catch((e) => {
                if (e.name !== "AbortError") setError(e.message);
            });
        return () => c.abort();
    }, [retry]);
    // Optional analytics keys are rendered only when the backend supplies them; no fabricated chart values.

    const centers = Array.isArray(data?.centers) ? data.centers : [];
    const totalTypes = types.reduce(
        (sum, item) => sum + Number(item.value || 0),
        0,
    );

    return (
        <>
            <section className="yp-hero">
                <div className="yp-hero-copy">
                    <h1>
                        Welcome back, <span>{partnerName}</span>
                    </h1>
                    <p>
                        Here's what's happening with your learners, field agents
                        and event transactions.
                    </p>
                </div>
                <div className="yp-hero-art">
                    <div className="yp-hero-symbol">
                        <UsersRound size={57} />
                        <ChartNoAxesCombined size={37} />
                    </div>
                    <div className="yp-hero-quote">
                        “ Together we build
                        <br /> brighter futures”
                        <i />
                    </div>
                </div>
            </section>
            <div className="yp-content">
                {error && (
                    <div className="yp-error" role="alert">
                        {error}{" "}
                        <button
                            type="button"
                            onClick={() => setRetry((v) => v + 1)}
                        >
                            Retry
                        </button>
                    </div>
                )}
                <section className="yp-stats" aria-busy={!data && !error}>
                    <StatCard
                        label="Total Learners"
                        value={fmt(data?.total_learners)}
                        icon={GraduationCap}
                        tone="blue"
                        description="Learners in your network"
                        onClick={() => onNavigate("learners")}
                    />
                    <StatCard
                        label="Field Agents"
                        value={fmt(data?.total_field_agents)}
                        icon={UsersRound}
                        tone="green"
                        description="Agents in your network"
                        onClick={() => onNavigate("agents")}
                    />
                    <StatCard
                        label="Event Transactions"
                        value={fmt(data?.total_event_transactions)}
                        icon={CalendarDays}
                        tone="amber"
                        description="Transactions recorded"
                        onClick={() => onNavigate("transactions")}
                    />
                </section>
                <div className="yp-dashboard-grid">
                    <section className="yp-panel yp-growth">
                        <div className="yp-panel-heading">
                            <h2>
                                <ChartNoAxesCombined size={23} />
                                Growth Overview
                            </h2>

                            <select
                                className="yp-period"
                                value={growthPeriod}
                                onChange={(e) =>
                                    setGrowthPeriod(Number(e.target.value))
                                }
                            >
                                <option value={3}>Last 3 Months</option>
                                <option value={6}>Last 6 Months</option>
                                <option value={12}>Last 12 Months</option>
                            </select>
                        </div>

                        <div className="yp-legend">
                            <span>
                                <i style={{ background: colors[0] }} />
                                Learners
                            </span>

                            <span>
                                <i style={{ background: colors[1] }} />
                                Field Agents
                            </span>

                            <span>
                                <i style={{ background: colors[2] }} />
                                Event Transactions
                            </span>
                        </div>

                        {growthLoading ? (
                            <div className="yp-empty">
                                Loading growth analytics...
                            </div>
                        ) : growthError ? (
                            <div className="yp-empty">{growthError}</div>
                        ) : growth.length ? (
                            <div className="yp-chart">
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart
                                        data={growth}
                                        margin={{
                                            top: 12,
                                            right: 12,
                                            left: -17,
                                            bottom: 0,
                                        }}
                                    >
                                        <defs>
                                            {[
                                                ["learners", colors[0]],
                                                ["field_agents", colors[1]],
                                                [
                                                    "event_transactions",
                                                    colors[2],
                                                ],
                                            ].map(([key, color]) => (
                                                <linearGradient
                                                    key={key}
                                                    id={`yp-${key}`}
                                                    x1="0"
                                                    y1="0"
                                                    x2="0"
                                                    y2="1"
                                                >
                                                    <stop
                                                        offset="0%"
                                                        stopColor={color}
                                                        stopOpacity={0.17}
                                                    />
                                                    <stop
                                                        offset="95%"
                                                        stopColor={color}
                                                        stopOpacity={0}
                                                    />
                                                </linearGradient>
                                            ))}
                                        </defs>

                                        <CartesianGrid
                                            stroke="#e8eff8"
                                            vertical={false}
                                        />

                                        <XAxis
                                            dataKey="month"
                                            tickLine={false}
                                            axisLine={false}
                                            tick={{
                                                fill: "#6480ac",
                                                fontSize: 11,
                                            }}
                                        />

                                        <YAxis
                                            tickLine={false}
                                            axisLine={false}
                                            allowDecimals={false}
                                            tick={{
                                                fill: "#6480ac",
                                                fontSize: 11,
                                            }}
                                        />

                                        <Tooltip />

                                        {[
                                            ["learners", colors[0]],
                                            ["field_agents", colors[1]],
                                            ["event_transactions", colors[2]],
                                        ].map(([key, color]) => (
                                            <Area
                                                key={key}
                                                type="monotone"
                                                dataKey={key}
                                                stroke={color}
                                                strokeWidth={2.5}
                                                fill={`url(#yp-${key})`}
                                                dot={false}
                                            />
                                        ))}
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        ) : (
                            <div className="yp-empty">
                                No growth analytics available.
                            </div>
                        )}
                    </section>

                    <section className="yp-panel yp-types">
                        <div className="yp-panel-heading">
                            <h2>
                                <ChartPie size={22} />
                                Event Transactions by Type
                            </h2>
                        </div>

                        {typesLoading ? (
                            <div className="yp-empty">
                                Loading transaction breakdown...
                            </div>
                        ) : typesError ? (
                            <div className="yp-empty">{typesError}</div>
                        ) : totalTypes > 0 ? (
                            <div className="yp-donut-layout">
                                <div className="yp-donut">
                                    <ResponsiveContainer
                                        width="100%"
                                        height="100%"
                                    >
                                        <PieChart>
                                            <Pie
                                                data={types}
                                                dataKey="value"
                                                nameKey="name"
                                                innerRadius="66%"
                                                outerRadius="90%"
                                                stroke="#fff"
                                                strokeWidth={3}
                                            >
                                                {types.map((item, i) => (
                                                    <Cell
                                                        key={item.name || i}
                                                        fill={
                                                            colors[
                                                                i %
                                                                    colors.length
                                                            ]
                                                        }
                                                    />
                                                ))}
                                            </Pie>

                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>

                                    <div className="yp-donut-center">
                                        <strong>{fmt(totalTypes)}</strong>
                                        <span>Total</span>
                                    </div>
                                </div>

                                <div className="yp-type-list">
                                    {types.map((item, i) => (
                                        <div key={item.name || i}>
                                            <i
                                                style={{
                                                    background:
                                                        colors[
                                                            i % colors.length
                                                        ],
                                                }}
                                            />

                                            <span>{item.name}</span>

                                            <strong>
                                                {totalTypes
                                                    ? Math.round(
                                                          (Number(
                                                              item.value || 0,
                                                          ) /
                                                              totalTypes) *
                                                              100,
                                                      )
                                                    : 0}
                                                %
                                            </strong>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <div className="yp-empty">
                                No event transactions available.
                            </div>
                        )}
                    </section>

                    <RecentActivities onNavigate={onNavigate} />
                    <Top10FieldAgentsChart onNavigate={onNavigate} />
                    <LearnerAgePieChart onNavigate={onNavigate} />
                    <EventTransactionPieChart onNavigate={onNavigate} />
                    <section className="yp-panel yp-centers">
                        <div className="yp-panel-heading">
                            <div>
                                <h2>
                                    <Building2 size={22} /> Your Centers
                                </h2>
                                <p>Centers linked to this partner account.</p>
                            </div>
                            <span className="yp-count-pill">
                                {centers.length} total
                            </span>
                        </div>
                        {centers.length ? (
                            <div className="yp-table-wrap">
                                <table className="yp-table">
                                    <thead>
                                        <tr>
                                            <th>Center Name</th>
                                            <th>Partner Code</th>
                                            <th>Status</th>
                                            <th>Learners</th>
                                            <th>Field Agents</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {centers.map((c, i) => (
                                            <tr key={c.id || i}>
                                                <td>
                                                    <span className="yp-center-name">
                                                        <span className="yp-center-icon">
                                                            <Building2
                                                                size={17}
                                                            />
                                                        </span>
                                                        {c.name}
                                                    </span>
                                                </td>
                                                <td>{c.code || "—"}</td>
                                                <td>
                                                    <span
                                                        className={`yp-status ${String(c.status || "").toLowerCase() === "active" ? "is-active" : ""}`}
                                                    >
                                                        {c.status || "—"}
                                                    </span>
                                                </td>
                                                <td>{fmt(c.learners_count)}</td>
                                                <td>
                                                    {fmt(c.field_agents_count)}
                                                </td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        className="yp-link"
                                                        onClick={() =>
                                                            onNavigate("agents")
                                                        }
                                                    >
                                                        View Details{" "}
                                                        <ArrowRight size={14} />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="yp-empty yp-empty--short">
                                Centers will appear here when included in the
                                dashboard API.
                            </div>
                        )}
                    </section>
                    <section className="yp-promo">
                        <div>
                            <Target size={29} />
                            <h2>
                                Empowering
                                <br />
                                Young Talent Together
                            </h2>
                            <p>
                                Your partnership helps create opportunities,
                                build skills and shape brighter futures.
                            </p>
                            <button
                                type="button"
                                onClick={() => onNavigate("reports")}
                            >
                                View Reports <ArrowRight size={17} />
                            </button>
                        </div>
                        <div className="yp-promo-plant">
                            <Leaf size={90} />
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}
