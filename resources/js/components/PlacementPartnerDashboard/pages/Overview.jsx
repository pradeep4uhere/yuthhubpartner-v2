import { useCallback, useEffect, useState } from "react";
import {
    Building2,
    GraduationCap,
    UsersRound,
    CalendarDays,
    CheckCircle2,
    ArrowRight,
    AlertCircle,
} from "lucide-react";

const API_URL = "/api/partner-placement/dashboard/summary";

const initialSummary = {
    total_field_agents: 0,
    total_learners: 0,
    total_event_transactions: 0,
    total_accepted: 0,
};

const formatNumber = (value) => Number(value ?? 0).toLocaleString("en-IN");

export default function Overview({ placementPartner = {}, onNavigate }) {
    const [summary, setSummary] = useState(initialSummary);
    const [fieldAgents, setFieldAgents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [retryKey, setRetryKey] = useState(0);

    const fetchDashboard = useCallback(async (signal) => {
        setLoading(true);
        setError("");

        try {
            const response = await fetch(API_URL, {
                method: "GET",
                credentials: "same-origin",
                headers: { Accept: "application/json" },
                signal,
            });

            if (!response.ok) {
                throw new Error(
                    `Unable to load dashboard (${response.status})`,
                );
            }

            const data = await response.json();
            if (signal.aborted) return;

            setSummary({ ...initialSummary, ...(data.summary || {}) });
            setFieldAgents(
                Array.isArray(data.field_agents) ? data.field_agents : [],
            );
        } catch (err) {
            if (!signal.aborted && err.name !== "AbortError") {
                setError(err.message || "Unable to load dashboard.");
            }
        } finally {
            if (!signal.aborted) setLoading(false);
        }
    }, []);

    useEffect(() => {
        const controller = new AbortController();
        fetchDashboard(controller.signal);
        return () => controller.abort();
    }, [fetchDashboard, retryKey]);

    const cards = [
        {
            label: "Associated Field Agents",
            value: summary.total_field_agents,
            icon: UsersRound,
            page: "agents",
        },
        {
            label: "Total Learners",
            value: summary.total_learners,
            icon: GraduationCap,
            page: "learners",
        },
        {
            label: "Event Transactions",
            value: summary.total_event_transactions,
            icon: CalendarDays,
            page: "transactions",
        },
        {
            label: "Accepted Transactions",
            value: summary.total_accepted,
            icon: CheckCircle2,
            page: "transactions",
        },
    ];

    return (
        <>
            <section className="yp-hero">
                <div>
                    <h1>
                        Welcome,{" "}
                        <span>
                            {placementPartner.name || "Placement Partner"}
                        </span>
                    </h1>
                    <p>
                        Manage your associated Field Agents and monitor
                        placement performance in one workspace.
                    </p>
                </div>
            </section>

            <div className="yp-content">
                {error && (
                    <div className="yp-error" role="alert">
                        <AlertCircle size={16} /> <span>{error}</span>{" "}
                        <button
                            type="button"
                            onClick={() => setRetryKey((key) => key + 1)}
                        >
                            Retry
                        </button>
                    </div>
                )}

                <div className="yp-stats ypp-stats">
                    {cards.map(({ label, value, icon: Icon, page }) => (
                        <article className="yp-stat" key={label}>
                            <span className="yp-stat-icon">
                                <Icon size={20} />
                            </span>
                            <span className="yp-stat-copy">
                                <span>{label}</span>
                                <strong>
                                    {loading
                                        ? "..."
                                        : error
                                          ? "—"
                                          : formatNumber(value)}
                                </strong>
                                <small>
                                    {loading
                                        ? "Loading..."
                                        : error
                                          ? "Data unavailable"
                                          : "Associated network"}
                                </small>
                            </span>
                            <button
                                type="button"
                                className="yp-stat-arrow"
                                onClick={() => onNavigate?.(page)}
                                aria-label={`View ${label}`}
                            >
                                <ArrowRight size={15} />
                            </button>
                        </article>
                    ))}
                </div>

                <div className="yp-dashboard-grid ypp-grid">
                    <section className="yp-panel">
                        <div className="yp-panel-heading">
                            <h2>
                                <UsersRound size={17} /> Associated Field Agents
                            </h2>
                            <button
                                className="yp-link"
                                type="button"
                                onClick={() => onNavigate?.("agents")}
                            >
                                View All <ArrowRight size={15} />
                            </button>
                        </div>

                        {loading ? (
                            <div className="yp-empty">
                                Loading associated Field Agents...
                            </div>
                        ) : error ? (
                            <div className="yp-empty">
                                Field Agent data is currently unavailable.
                            </div>
                        ) : fieldAgents.length === 0 ? (
                            <div className="yp-empty">
                                No associated Field Agents found.
                            </div>
                        ) : (
                            <div className="yp-table-wrap">
                                <table className="yp-table">
                                    <thead>
                                        <tr>
                                            <th>Field Agent</th>
                                            <th>CSC ID</th>
                                            <th>State</th>
                                            <th>District</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {fieldAgents.map((agent) => (
                                            <tr key={agent.id}>
                                                <td>
                                                    <strong>
                                                        {agent.name || "—"}
                                                    </strong>
                                                </td>
                                                <td>{agent.csc_id || "—"}</td>
                                                <td>{agent.state || "—"}</td>
                                                <td>{agent.district || "—"}</td>
                                                <td>
                                                    <span
                                                        className={`yp-status ${agent.status === "active" ? "is-active" : ""}`}
                                                    >
                                                        {agent.status ||
                                                            "Unknown"}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>

                    <section className="yp-panel">
                        <div className="yp-panel-heading">
                            <h2>
                                <Building2 size={17} /> Organisation Information
                            </h2>
                        </div>
                        <dl className="ypp-details">
                            <dt>Organisation</dt>
                            <dd>{placementPartner.organisation || "—"}</dd>
                            <dt>Placement Code</dt>
                            <dd>{placementPartner.placementCode || "—"}</dd>
                            <dt>Account Status</dt>
                            <dd>
                                <span
                                    className={`yp-status ${placementPartner.status === "active" ? "is-active" : ""}`}
                                >
                                    {placementPartner.status || "—"}
                                </span>
                            </dd>
                            <dt>Contact Person</dt>
                            <dd>{placementPartner.name || "—"}</dd>
                            <dt>Email</dt>
                            <dd>{placementPartner.email || "—"}</dd>
                            <dt>Contact Number</dt>
                            <dd>{placementPartner.phone || "—"}</dd>
                        </dl>
                    </section>
                </div>
            </div>
        </>
    );
}
