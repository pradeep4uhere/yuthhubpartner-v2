import { useEffect, useMemo, useState } from "react";

import {
    ResponsiveContainer,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    Cell,
} from "recharts";

import {
    Activity,
    CheckCircle2,
    RotateCcw,
    TrendingUp,
    AlertCircle,
    ChevronDown,
} from "lucide-react";

import "./EventTransactionPieChart.css";

const API_URL = "/api/partner/dashboard/event-review-performance";

const COLORS = {
    open: "#64748b",
    pending: "#f59e0b",
    accepted: "#10b981",
    rejected: "#ef4444",
    returned: "#8b5cf6",
    other: "#cbd5e1",
};

const STATUS_KEYS = [
    "accepted",
    "returned",
    "rejected",
    "pending",
    "open",
    "other",
];

const formatNumber = (value) => Number(value || 0).toLocaleString("en-IN");

const formatPercent = (value) => `${Number(value || 0).toFixed(1)}%`;

/* Custom Tooltip */
function PremiumTooltip({ active, payload, label }) {
    if (!active || !payload?.length) return null;

    const total = payload.reduce(
        (sum, item) => sum + Number(item.value || 0),
        0,
    );

    return (
        <div className="erp-tooltip">
            <div className="erp-tooltip__heading">
                <strong>{label}</strong>
                <span>{formatNumber(total)} transactions</span>
            </div>

            {payload
                .filter((item) => Number(item.value) > 0)
                .map((item) => (
                    <div className="erp-tooltip__row" key={item.dataKey}>
                        <span
                            className="erp-tooltip__dot"
                            style={{
                                background: item.color,
                            }}
                        />

                        <span>{item.name}</span>

                        <strong>{formatNumber(item.value)}</strong>
                    </div>
                ))}
        </div>
    );
}

/* KPI Card */
function MetricCard({ title, value, subtitle, icon: Icon, tone = "blue" }) {
    return (
        <div className={`erp-metric erp-metric--${tone}`}>
            <div className="erp-metric__top">
                <span>{title}</span>

                <div className="erp-metric__icon">
                    <Icon size={19} strokeWidth={2} />
                </div>
            </div>

            <strong>{value}</strong>

            <small>{subtitle}</small>
        </div>
    );
}

export default function EventReviewPerformance() {
    const [period, setPeriod] = useState("month");
    const [centerId, setCenterId] = useState("");

    const [centers, setCenters] = useState([]);
    const [chartData, setChartData] = useState([]);

    const [total, setTotal] = useState(0);
    const [statusTotals, setStatusTotals] = useState({});

    const [dateRange, setDateRange] = useState({
        from: "",
        to: "",
    });

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        const controller = new AbortController();

        const fetchPerformance = async () => {
            setLoading(true);
            setError("");

            try {
                const params = new URLSearchParams({
                    period,
                });

                if (centerId) {
                    params.append("partner_center_id", centerId);
                }

                const response = await fetch(
                    `${API_URL}?${params.toString()}`,
                    {
                        headers: {
                            Accept: "application/json",
                        },
                        credentials: "same-origin",
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error("Unable to load review performance.");
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(
                        result.message || "Unable to load analytics.",
                    );
                }

                if (controller.signal.aborted) return;

                setCenters(result.centers ?? []);

                setTotal(Number(result.total ?? 0));

                setStatusTotals(result.status_totals ?? {});

                setDateRange({
                    from: result.from ?? "",
                    to: result.to ?? "",
                });

                // Normalize data and calculate unknown statuses
                const formatted = (result.data ?? []).map((item) => {
                    const knownTotal =
                        Number(item.open || 0) +
                        Number(item.pending || 0) +
                        Number(item.accepted || 0) +
                        Number(item.rejected || 0) +
                        Number(item.returned || 0);

                    return {
                        ...item,
                        total: Number(item.total || 0),

                        open: Number(item.open || 0),
                        pending: Number(item.pending || 0),
                        accepted: Number(item.accepted || 0),
                        rejected: Number(item.rejected || 0),
                        returned: Number(item.returned || 0),

                        other: Math.max(
                            0,
                            Number(item.total || 0) - knownTotal,
                        ),
                    };
                });

                setChartData(formatted);
            } catch (err) {
                if (err.name !== "AbortError") {
                    setError(err.message);
                    setChartData([]);
                    setTotal(0);
                    setStatusTotals({});
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        };

        fetchPerformance();

        return () => controller.abort();
    }, [period, centerId]);

    const metrics = useMemo(() => {
        const accepted = Number(statusTotals.accepted || 0);

        const returned = Number(statusTotals.returned || 0);

        const rejected = Number(statusTotals.rejected || 0);

        return {
            accepted,
            returned,
            rejected,

            acceptanceRate: total > 0 ? (accepted / total) * 100 : 0,
        };
    }, [statusTotals, total]);

    return (
        <section className="erp-card">
            {/* Header */}
            <div className="erp-header">
                <div className="erp-header__title">
                    <div className="erp-heading-icon">
                        <Activity size={21} />
                    </div>

                    <div>
                        <h2>Event Review Analytics</h2>

                        <p>
                            Monitor review performance across your partner
                            centers
                        </p>
                    </div>
                </div>

                <div className="erp-filters">
                    <div className="erp-select-wrap">
                        <select
                            value={centerId}
                            onChange={(e) => setCenterId(e.target.value)}
                            aria-label="Select partner center"
                        >
                            <option value="">All Centers</option>

                            {centers.map((center) => (
                                <option key={center.id} value={center.id}>
                                    {center.name}
                                </option>
                            ))}
                        </select>

                        <ChevronDown size={15} />
                    </div>

                    <div className="erp-select-wrap">
                        <select
                            value={period}
                            onChange={(e) => setPeriod(e.target.value)}
                            aria-label="Select reporting period"
                        >
                            <option value="day">Last 30 Days</option>

                            <option value="week">Last 12 Weeks</option>

                            <option value="month">Last 12 Months</option>

                            <option value="year">Last 5 Years</option>
                        </select>

                        <ChevronDown size={15} />
                    </div>
                </div>
            </div>

            {/* KPI Cards */}
            <div className="erp-metrics">
                <MetricCard
                    title="Total Transactions"
                    value={formatNumber(total)}
                    subtitle="Across selected centers"
                    icon={Activity}
                    tone="blue"
                />

                <MetricCard
                    title="Accepted"
                    value={formatNumber(metrics.accepted)}
                    subtitle="Successfully reviewed"
                    icon={CheckCircle2}
                    tone="green"
                />

                <MetricCard
                    title="Returned"
                    value={formatNumber(metrics.returned)}
                    subtitle="Returned for correction"
                    icon={RotateCcw}
                    tone="purple"
                />

                <MetricCard
                    title="Acceptance Rate"
                    value={formatPercent(metrics.acceptanceRate)}
                    subtitle="Of all transactions"
                    icon={TrendingUp}
                    tone="amber"
                />
            </div>

            {/* Chart Heading */}
            <div className="erp-chart-heading">
                <div>
                    <h3>Review Status Breakdown</h3>

                    <p>Transaction distribution by partner center</p>
                </div>

                {dateRange.from && (
                    <span className="erp-date-range">
                        {dateRange.from} — {dateRange.to}
                    </span>
                )}
            </div>

            {/* Chart */}
            {loading ? (
                <div className="erp-empty">
                    <div className="erp-spinner" />
                    Loading analytics...
                </div>
            ) : error ? (
                <div className="erp-empty erp-error">
                    <AlertCircle size={22} />
                    {error}
                </div>
            ) : chartData.length === 0 ? (
                <div className="erp-empty">
                    No transactions found for this period.
                </div>
            ) : (
                <div className="erp-chart">
                    <ResponsiveContainer
                        width="100%"
                        height={Math.max(280, chartData.length * 78 + 90)}
                    >
                        <BarChart
                            data={chartData}
                            layout="vertical"
                            barSize={24}
                            margin={{
                                top: 15,
                                right: 25,
                                left: 15,
                                bottom: 10,
                            }}
                        >
                            <defs>
                                <linearGradient
                                    id="erpAcceptedGradient"
                                    x1="0"
                                    y1="0"
                                    x2="1"
                                    y2="0"
                                >
                                    <stop offset="0%" stopColor="#10b981" />
                                    <stop offset="100%" stopColor="#34d399" />
                                </linearGradient>
                            </defs>

                            <CartesianGrid
                                stroke="#e2e8f0"
                                strokeDasharray="4 5"
                                horizontal={false}
                            />

                            <XAxis
                                type="number"
                                allowDecimals={false}
                                tickFormatter={formatNumber}
                                tick={{
                                    fill: "#94a3b8",
                                    fontSize: 11,
                                }}
                                axisLine={false}
                                tickLine={false}
                            />

                            <YAxis
                                type="category"
                                dataKey="center_name"
                                width={125}
                                tick={{
                                    fill: "#475569",
                                    fontSize: 12,
                                    fontWeight: 500,
                                }}
                                axisLine={false}
                                tickLine={false}
                            />

                            <Tooltip
                                content={<PremiumTooltip />}
                                cursor={{
                                    fill: "#f8fafc",
                                }}
                            />

                            <Legend
                                verticalAlign="bottom"
                                iconType="circle"
                                wrapperStyle={{
                                    paddingTop: 20,
                                    fontSize: 12,
                                }}
                            />

                            <Bar
                                dataKey="accepted"
                                name="Accepted"
                                stackId="reviews"
                                fill="url(#erpAcceptedGradient)"
                                animationDuration={850}
                            />

                            <Bar
                                dataKey="returned"
                                name="Returned"
                                stackId="reviews"
                                fill={COLORS.returned}
                                animationDuration={850}
                            />

                            <Bar
                                dataKey="rejected"
                                name="Rejected"
                                stackId="reviews"
                                fill={COLORS.rejected}
                                animationDuration={850}
                            />

                            <Bar
                                dataKey="pending"
                                name="Pending"
                                stackId="reviews"
                                fill={COLORS.pending}
                                animationDuration={850}
                            />

                            <Bar
                                dataKey="open"
                                name="Open"
                                stackId="reviews"
                                fill={COLORS.open}
                                animationDuration={850}
                            />

                            <Bar
                                dataKey="other"
                                name="Other"
                                stackId="reviews"
                                fill={COLORS.other}
                                radius={[5, 5, 5, 5]}
                                animationDuration={850}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}

            {/* Footer */}
            <div className="erp-footer">
                <span className="erp-footer__dot" />
                Data filtered for authenticated partner only
                <span className="erp-footer__separator">•</span>
                {centers.length} partner centers
            </div>
        </section>
    );
}
