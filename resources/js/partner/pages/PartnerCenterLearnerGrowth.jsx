import { useEffect, useId, useMemo, useState } from "react";

import {
    ResponsiveContainer,
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
} from "recharts";

import {
    Users,
    UserPlus,
    TrendingUp,
    CalendarDays,
    ChevronDown,
    AlertCircle,
    Activity,
} from "lucide-react";

import "./PartnerCenterLearnerGrowth.css";

const API_URL = "/api/partner/dashboard/learner-registration-growth";

const formatNumber = (value) => Number(value || 0).toLocaleString("en-IN");

function GrowthTooltip({ active, payload }) {
    if (!active || !payload?.length) return null;

    const item = payload[0]?.payload;

    if (!item) return null;

    return (
        <div className="pclg-tooltip">
            <span className="pclg-tooltip__date">{item.label}</span>

            <div className="pclg-tooltip__value">
                <span className="pclg-tooltip__dot" />

                <span>Registrations</span>

                <strong>{formatNumber(item.registrations)}</strong>
            </div>
        </div>
    );
}

function MetricCard({ title, value, subtitle, icon: Icon, tone = "indigo" }) {
    return (
        <div className={`pclg-metric pclg-metric--${tone}`}>
            <div className="pclg-metric__top">
                <span>{title}</span>

                <div className="pclg-metric__icon">
                    <Icon size={19} strokeWidth={2} />
                </div>
            </div>

            <strong>{value}</strong>

            <small>{subtitle}</small>
        </div>
    );
}

export default function PartnerCenterLearnerGrowth() {
    const gradientId = useId().replace(/:/g, "");

    const [period, setPeriod] = useState("month");
    const [centerId, setCenterId] = useState("");

    const [centers, setCenters] = useState([]);
    const [chartData, setChartData] = useState([]);

    const [summary, setSummary] = useState({
        total_registrations: 0,
        current_period: 0,
        previous_period: 0,
        growth_percentage: null,
        peak_registrations: 0,
        peak_period: null,
    });

    const [dateRange, setDateRange] = useState({
        from: "",
        to: "",
    });

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        const controller = new AbortController();

        const loadGrowth = async () => {
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
                        method: "GET",
                        headers: {
                            Accept: "application/json",
                        },
                        credentials: "same-origin",
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error(
                        "Unable to load learner registration growth.",
                    );
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(
                        result.message || "Unable to load learner analytics.",
                    );
                }

                if (controller.signal.aborted) return;

                setCenters(result.centers ?? []);

                setChartData(
                    (result.data ?? []).map((item) => ({
                        ...item,
                        registrations: Number(item.registrations ?? 0),
                    })),
                );

                setSummary(
                    result.summary ?? {
                        total_registrations: 0,
                        current_period: 0,
                        previous_period: 0,
                        growth_percentage: null,
                        peak_registrations: 0,
                        peak_period: null,
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
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        };

        loadGrowth();

        return () => controller.abort();
    }, [period, centerId]);

    const growth = summary.growth_percentage;

    const growthLabel =
        growth === null || growth === undefined
            ? "N/A"
            : `${Number(growth) > 0 ? "+" : ""}${Number(growth).toFixed(1)}%`;

    const growthTone =
        growth === null || growth === undefined
            ? "neutral"
            : Number(growth) > 0
              ? "positive"
              : Number(growth) < 0
                ? "negative"
                : "neutral";

    const selectedCenterName = useMemo(() => {
        if (!centerId) return "All Partner Centers";

        return (
            centers.find((center) => String(center.id) === String(centerId))
                ?.name ?? "Selected Center"
        );
    }, [centers, centerId]);

    const hasRegistrations = chartData.some((item) => item.registrations > 0);

    return (
        <section className="pclg-card">
            {/* Header */}
            <div className="pclg-header">
                <div className="pclg-heading">
                    <div className="pclg-heading__icon">
                        <Activity size={22} />
                    </div>

                    <div>
                        <h2>Learner Registration Growth</h2>

                        <p>
                            Monitor registration trends across your partner
                            centers
                        </p>
                    </div>
                </div>

                <div className="pclg-filters">
                    <div className="pclg-select">
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

                    <div className="pclg-select">
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
            <div className="pclg-metrics">
                <MetricCard
                    title="Total Registrations"
                    value={formatNumber(summary.total_registrations)}
                    subtitle="Within selected date range"
                    icon={Users}
                    tone="indigo"
                />

                <MetricCard
                    title="Current Period"
                    value={formatNumber(summary.current_period)}
                    subtitle="Registrations so far"
                    icon={UserPlus}
                    tone="green"
                />

                <MetricCard
                    title="Growth Rate"
                    value={growthLabel}
                    subtitle="Compared with previous period"
                    icon={TrendingUp}
                    tone={growthTone}
                />

                <MetricCard
                    title="Peak Registrations"
                    value={formatNumber(summary.peak_registrations)}
                    subtitle={
                        summary.peak_period
                            ? `Highest: ${summary.peak_period}`
                            : "No registrations yet"
                    }
                    icon={CalendarDays}
                    tone="amber"
                />
            </div>

            {/* Chart Heading */}
            <div className="pclg-chart-heading">
                <div>
                    <h3>Registration Trend</h3>

                    <p>
                        {selectedCenterName}
                        {" · "}
                        Learners registered over time
                    </p>
                </div>

                {dateRange.from && (
                    <span className="pclg-date">
                        {dateRange.from} — {dateRange.to}
                    </span>
                )}
            </div>

            {/* Chart */}
            {loading ? (
                <div className="pclg-empty">
                    <div className="pclg-spinner" />
                    Loading registration analytics...
                </div>
            ) : error ? (
                <div className="pclg-empty pclg-error" role="alert">
                    <AlertCircle size={22} />
                    {error}
                </div>
            ) : !hasRegistrations ? (
                <div className="pclg-empty">
                    <Users size={30} />
                    No learner registrations found for this period.
                </div>
            ) : (
                <div className="pclg-chart">
                    <ResponsiveContainer width="100%" height={340}>
                        <AreaChart
                            data={chartData}
                            margin={{
                                top: 20,
                                right: 20,
                                left: 0,
                                bottom: 5,
                            }}
                        >
                            <defs>
                                <linearGradient
                                    id={gradientId}
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0%"
                                        stopColor="#6366f1"
                                        stopOpacity={0.28}
                                    />

                                    <stop
                                        offset="95%"
                                        stopColor="#6366f1"
                                        stopOpacity={0.01}
                                    />
                                </linearGradient>
                            </defs>

                            <CartesianGrid
                                vertical={false}
                                stroke="#e9edf5"
                                strokeDasharray="4 5"
                            />

                            <XAxis
                                dataKey="label"
                                tick={{
                                    fill: "#94a3b8",
                                    fontSize: 11,
                                }}
                                tickLine={false}
                                axisLine={false}
                                minTickGap={25}
                                dy={10}
                            />

                            <YAxis
                                allowDecimals={false}
                                tick={{
                                    fill: "#94a3b8",
                                    fontSize: 11,
                                }}
                                tickLine={false}
                                axisLine={false}
                                width={45}
                                tickFormatter={formatNumber}
                            />

                            <Tooltip
                                content={<GrowthTooltip />}
                                cursor={{
                                    stroke: "#818cf8",
                                    strokeWidth: 1,
                                    strokeDasharray: "4 4",
                                }}
                            />

                            <Area
                                type="monotone"
                                dataKey="registrations"
                                name="Registrations"
                                stroke="#6366f1"
                                strokeWidth={3}
                                fill={`url(#${gradientId})`}
                                activeDot={{
                                    r: 6,
                                    stroke: "#ffffff",
                                    strokeWidth: 3,
                                    fill: "#6366f1",
                                }}
                                dot={
                                    period === "month" || period === "year"
                                        ? {
                                              r: 3,
                                              fill: "#ffffff",
                                              stroke: "#6366f1",
                                              strokeWidth: 2,
                                          }
                                        : false
                                }
                                animationDuration={950}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            )}

            {/* Footer */}
            <div className="pclg-footer">
                <span className="pclg-footer__dot" />
                Authenticated partner learner data only
                <span>•</span>
                {centers.length} partner centers
            </div>
        </section>
    );
}
