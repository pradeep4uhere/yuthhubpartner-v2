import { useEffect, useState } from "react";
import {
    ResponsiveContainer,
    LineChart,
    Line,
    CartesianGrid,
    XAxis,
    YAxis,
    Tooltip,
    Legend,
} from "recharts";

const formatNumber = (value) => Number(value ?? 0).toLocaleString("en-IN");

function AgentTooltip({ active, payload }) {
    if (!active || !payload?.length) {
        return null;
    }

    const agent = payload[0]?.payload;

    if (!agent) {
        return null;
    }

    return (
        <div className="top-agents-tooltip">
            <span className="top-agents-tooltip__rank">Rank #{agent.rank}</span>

            <strong className="top-agents-tooltip__name">{agent.name}</strong>

            <div className="top-agents-tooltip__value">
                <span className="top-agents-tooltip__dot" />
                <span>Registered learners</span>
                <strong>{formatNumber(agent.learners)}</strong>
            </div>
        </div>
    );
}

export default function Top10FieldAgentsChart() {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        const controller = new AbortController();

        fetch("/api/partner/dashboard/top-10-field-agents", {
            headers: {
                Accept: "application/json",
            },
            credentials: "same-origin",
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(
                        `Unable to load field agents (HTTP ${response.status}).`,
                    );
                }

                return response.json();
            })
            .then((result) => {
                if (result.success !== true) {
                    throw new Error("Unable to load field agent statistics.");
                }

                setData(
                    (result.data ?? []).map((agent) => ({
                        ...agent,
                        learners: Number(agent.learners),
                    })),
                );
            })
            .catch((err) => {
                if (err.name !== "AbortError") {
                    console.error("Top 10 field agents:", err);
                    setError(err.message);
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            });

        return () => controller.abort();
    }, []);

    const highestAgent = data.length
        ? data.reduce((highest, agent) =>
              agent.learners > highest.learners ? agent : highest,
          )
        : null;

    return (
        <section className="top-agents-card">
            <div className="top-agents-card__header">
                <div>
                    <h2>Top 10 Field Agents</h2>
                    <p>Field agent performance by learner registrations</p>
                </div>

                <span className="top-agents-card__badge">
                    <span className="top-agents-card__badge-dot" />
                    Top performers
                </span>
            </div>

            {!loading && !error && data.length > 0 && (
                <div className="top-agents-summary">
                    <div className="top-agents-summary__icon">
                        <svg
                            viewBox="0 0 24 24"
                            width="23"
                            height="23"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.8"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M3 17l6-6 4 3 8-9" />
                            <path d="M15 5h6v6" />
                        </svg>
                    </div>

                    <div>
                        <span>Leading field agent</span>
                        <strong>{highestAgent.name}</strong>
                    </div>

                    <div className="top-agents-summary__count">
                        <strong>{formatNumber(highestAgent.learners)}</strong>
                        <span>learners</span>
                    </div>
                </div>
            )}

            <div className="top-agents-chart-area">
                {loading ? (
                    <div className="top-agents-state">
                        Loading field agent statistics...
                    </div>
                ) : error ? (
                    <div
                        className="top-agents-state top-agents-state--error"
                        role="alert"
                    >
                        {error}
                    </div>
                ) : data.length === 0 ? (
                    <div className="top-agents-state">
                        No field agent learner data available.
                    </div>
                ) : (
                    <ResponsiveContainer width="100%" height={340}>
                        <LineChart
                            data={data}
                            margin={{
                                top: 20,
                                right: 20,
                                left: 8,
                                bottom: 10,
                            }}
                        >
                            <CartesianGrid
                                stroke="#edf1f7"
                                strokeDasharray="4 5"
                                vertical={false}
                            />

                            <XAxis
                                dataKey="name"
                                axisLine={false}
                                tickLine={false}
                                tick={{
                                    fill: "#7b879b",
                                    fontSize: 11,
                                }}
                                tickMargin={14}
                                interval={0}
                                height={55}
                                tickFormatter={(name) =>
                                    name?.length > 10
                                        ? `${name.slice(0, 9)}…`
                                        : name
                                }
                            />

                            <YAxis
                                axisLine={false}
                                tickLine={false}
                                tick={{
                                    fill: "#8a94a6",
                                    fontSize: 11,
                                }}
                                tickFormatter={formatNumber}
                                width={55}
                                allowDecimals={false}
                                domain={[0, "auto"]}
                            />

                            <Tooltip
                                content={<AgentTooltip />}
                                cursor={{
                                    stroke: "#b7c8f8",
                                    strokeDasharray: "4 4",
                                }}
                            />

                            <Legend
                                verticalAlign="top"
                                align="right"
                                height={28}
                                iconType="circle"
                                iconSize={8}
                                formatter={() => (
                                    <span
                                        style={{
                                            color: "#6b778c",
                                            fontSize: 12,
                                        }}
                                    >
                                        Registered learners
                                    </span>
                                )}
                            />

                            <Line
                                type="monotone"
                                dataKey="learners"
                                name="Registered learners"
                                stroke="#4776e6"
                                strokeWidth={3}
                                dot={{
                                    r: 4,
                                    fill: "#ffffff",
                                    stroke: "#4776e6",
                                    strokeWidth: 2,
                                }}
                                activeDot={{
                                    r: 7,
                                    fill: "#4776e6",
                                    stroke: "#ffffff",
                                    strokeWidth: 3,
                                }}
                                animationDuration={700}
                                isAnimationActive={true}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                )}
            </div>

            {!loading && !error && data.length > 0 && (
                <div className="top-agents-card__footer">
                    <span>
                        <span className="top-agents-card__footer-dot" />
                        {data.length} field agents ranked
                    </span>

                    <span>Sorted by learner count</span>
                </div>
            )}
        </section>
    );
}
