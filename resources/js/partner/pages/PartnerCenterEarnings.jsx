import { useEffect, useMemo, useState } from "react";

import {
    ResponsiveContainer,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Cell,
    LabelList,
} from "recharts";

import {
    Wallet,
    IndianRupee,
    ReceiptText,
    TrendingUp,
    Trophy,
    ChevronDown,
    AlertCircle,
} from "lucide-react";

import "./PartnerCenterEarnings.css";

const API_URL = "/api/partner/dashboard/partner-center-earnings";

const BAR_COLORS = [
    ["#4f46e5", "#818cf8"],
    ["#0891b2", "#22d3ee"],
    ["#059669", "#34d399"],
    ["#d97706", "#fbbf24"],
    ["#7c3aed", "#c4b5fd"],
];

const money = (value) =>
    new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 2,
    }).format(Number(value || 0));

const compactMoney = (value) => {
    const amount = Number(value || 0);

    if (Math.abs(amount) >= 10000000)
        return `₹${(amount / 10000000).toFixed(2)}Cr`;

    if (Math.abs(amount) >= 100000) return `₹${(amount / 100000).toFixed(2)}L`;

    if (Math.abs(amount) >= 1000) return `₹${(amount / 1000).toFixed(1)}K`;

    return money(amount);
};

const number = (value) => Number(value || 0).toLocaleString("en-IN");

function EarningsTooltip({ active, payload }) {
    if (!active || !payload?.length) return null;

    const item = payload[0]?.payload;

    if (!item) return null;

    return (
        <div className="pce-tooltip">
            <strong>{item.center_name}</strong>

            <div className="pce-tooltip__amount">
                {money(item.total_earnings)}
            </div>

            <div className="pce-tooltip__row">
                <span>Transactions</span>
                <b>{number(item.transactions)}</b>
            </div>

            <div className="pce-tooltip__row">
                <span>Average Value</span>
                <b>{money(item.average_value)}</b>
            </div>
        </div>
    );
}

function EarningsMetric({
    label,
    value,
    subtitle,
    icon: Icon,
    tone = "indigo",
}) {
    return (
        <div className={`pce-metric pce-metric--${tone}`}>
            <div className="pce-metric__header">
                <span>{label}</span>

                <div className="pce-metric__icon">
                    <Icon size={19} />
                </div>
            </div>

            <strong title={String(value)}>{value}</strong>

            <small>{subtitle}</small>
        </div>
    );
}

export default function PartnerCenterEarnings() {
    const [period, setPeriod] = useState("month");
    const [centerId, setCenterId] = useState("");

    const [centers, setCenters] = useState([]);
    const [chartData, setChartData] = useState([]);

    const [summary, setSummary] = useState({
        total_earnings: 0,
        total_transactions: 0,
        average_transaction_value: 0,
        top_center: null,
    });

    const [dateRange, setDateRange] = useState({
        from: "",
        to: "",
    });

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        const controller = new AbortController();

        const loadEarnings = async () => {
            setLoading(true);
            setError("");

            try {
                const params = new URLSearchParams({
                    period,
                });

                if (centerId) {
                    params.set("partner_center_id", centerId);
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
                    throw new Error("Unable to load partner center earnings.");
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(
                        result.message || "Unable to load earnings.",
                    );
                }

                if (controller.signal.aborted) return;

                setCenters(result.centers ?? []);
                setChartData(result.data ?? []);

                setSummary(
                    result.summary ?? {
                        total_earnings: 0,
                        total_transactions: 0,
                        average_transaction_value: 0,
                        top_center: null,
                    },
                );

                setDateRange({
                    from: result.from ?? "",
                    to: result.to ?? "",
                });
            } catch (err) {
                if (err.name !== "AbortError") {
                    setError(err.message);
                    setChartData([]);
                    setSummary({
                        total_earnings: 0,
                        total_transactions: 0,
                        average_transaction_value: 0,
                        top_center: null,
                    });
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        };

        loadEarnings();

        return () => controller.abort();
    }, [period, centerId]);

    const data = useMemo(
        () =>
            chartData.map((item) => ({
                ...item,
                total_earnings: Number(item.total_earnings || 0),
                transactions: Number(item.transactions || 0),
                average_value: Number(item.average_value || 0),
            })),
        [chartData],
    );

    const hasEarnings = data.some((item) => item.total_earnings !== 0);

    return (
        <section className="pce-card">
            {/* Header */}
            <div className="pce-header">
                <div className="pce-heading">
                    <div className="pce-heading__icon">
                        <Wallet size={22} />
                    </div>

                    <div>
                        <h2>Partner Center Earnings</h2>
                        <p>Center-wise transaction value analytics</p>
                    </div>
                </div>

                <div className="pce-filters">
                    <div className="pce-select">
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

                    <div className="pce-select">
                        <select
                            value={period}
                            onChange={(e) => setPeriod(e.target.value)}
                            aria-label="Select period"
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
            <div className="pce-metrics">
                <EarningsMetric
                    label="Total Earnings"
                    value={money(summary.total_earnings)}
                    subtitle="Sum of event values"
                    icon={IndianRupee}
                    tone="indigo"
                />

                <EarningsMetric
                    label="Transactions"
                    value={number(summary.total_transactions)}
                    subtitle="Recorded transactions"
                    icon={ReceiptText}
                    tone="cyan"
                />

                <EarningsMetric
                    label="Average Value"
                    value={money(summary.average_transaction_value)}
                    subtitle="Per transaction"
                    icon={TrendingUp}
                    tone="green"
                />

                <EarningsMetric
                    label="Top Earning Center"
                    value={summary.top_center || "—"}
                    subtitle="Highest transaction value"
                    icon={Trophy}
                    tone="amber"
                />
            </div>

            {/* Chart Header */}
            <div className="pce-chart-heading">
                <div>
                    <h3>Earnings by Partner Center</h3>
                    <p>Compare center-wise transaction value</p>
                </div>

                {dateRange.from && (
                    <span className="pce-date">
                        {dateRange.from} — {dateRange.to}
                    </span>
                )}
            </div>

            {loading ? (
                <div className="pce-empty">
                    <div className="pce-spinner" />
                    Loading earnings analytics...
                </div>
            ) : error ? (
                <div className="pce-empty pce-error" role="alert">
                    <AlertCircle size={22} />
                    {error}
                </div>
            ) : !hasEarnings ? (
                <div className="pce-empty">
                    No earnings found for the selected period.
                </div>
            ) : (
                <div className="pce-chart">
                    <ResponsiveContainer
                        width="100%"
                        height={Math.max(280, data.length * 78 + 80)}
                    >
                        <BarChart
                            data={data}
                            layout="vertical"
                            barSize={25}
                            margin={{
                                top: 15,
                                right: 70,
                                left: 15,
                                bottom: 10,
                            }}
                        >
                            <defs>
                                {BAR_COLORS.map((colors, index) => (
                                    <linearGradient
                                        key={index}
                                        id={`pceGradient${index}`}
                                        x1="0"
                                        y1="0"
                                        x2="1"
                                        y2="0"
                                    >
                                        <stop
                                            offset="0%"
                                            stopColor={colors[0]}
                                        />
                                        <stop
                                            offset="100%"
                                            stopColor={colors[1]}
                                        />
                                    </linearGradient>
                                ))}
                            </defs>

                            <CartesianGrid
                                stroke="#e2e8f0"
                                strokeDasharray="4 5"
                                horizontal={false}
                            />

                            <XAxis
                                type="number"
                                tickFormatter={compactMoney}
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
                                content={<EarningsTooltip />}
                                cursor={{ fill: "#f8fafc" }}
                            />

                            <Bar
                                dataKey="total_earnings"
                                name="Earnings"
                                radius={[0, 7, 7, 0]}
                                animationDuration={900}
                            >
                                {data.map((item, index) => (
                                    <Cell
                                        key={item.partner_center_id}
                                        fill={`url(#pceGradient${
                                            index % BAR_COLORS.length
                                        })`}
                                    />
                                ))}

                                <LabelList
                                    dataKey="total_earnings"
                                    position="right"
                                    formatter={compactMoney}
                                    style={{
                                        fill: "#334155",
                                        fontSize: 11,
                                        fontWeight: 700,
                                    }}
                                />
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}

            <div className="pce-footer">
                <span className="pce-footer__dot" />
                Authenticated partner data only
                <span>•</span>
                {centers.length} partner centers
            </div>
        </section>
    );
}
