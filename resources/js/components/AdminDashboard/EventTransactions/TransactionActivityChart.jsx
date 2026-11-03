import { useEffect, useState } from "react";
import { Activity, RefreshCw } from "lucide-react";
import "./TransactionActivityChart.css";

const API_URL = "/admin/transaction-activity";

export default function TransactionActivityChart() {
    const [chartData, setChartData] = useState([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    // Fetch transaction activity for the last seven days.
    const fetchActivity = async (signal) => {
        setLoading(true);
        setError("");

        try {
            const response = await fetch(API_URL, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                signal,
            });

            if (!response.ok) {
                throw new Error(
                    `Unable to load transaction activity (${response.status}).`,
                );
            }

            const result = await response.json();
            const rows = Array.isArray(result.data) ? result.data : [];

            setChartData(rows);
            setTotal(Number(result.total || 0));
        } catch (err) {
            if (err.name !== "AbortError") {
                setError(err.message || "Something went wrong.");
            }
        } finally {
            if (!signal?.aborted) {
                setLoading(false);
            }
        }
    };

    useEffect(() => {
        const controller = new AbortController();

        fetchActivity(controller.signal);

        return () => controller.abort();
    }, []);

    const maxValue = Math.max(
        ...chartData.map((item) => Number(item.count || 0)),
        1,
    );

    // Format transaction counts using Indian number formatting.
    const formatNumber = (value) =>
        new Intl.NumberFormat("en-IN").format(Number(value || 0));

    return (
        <section className="aet-activity-card">
            <div className="aet-activity-heading">
                <div>
                    <span>TRANSACTION ACTIVITY</span>
                    <h2>Last 7 Days</h2>
                </div>

                <div className="aet-activity-total">{formatNumber(total)}</div>
            </div>

            {loading ? (
                <div className="aet-activity-message">
                    Loading transaction activity...
                </div>
            ) : error ? (
                <div className="aet-activity-message aet-activity-error">
                    <span>{error}</span>

                    <button
                        type="button"
                        onClick={() => {
                            const controller = new AbortController();
                            fetchActivity(controller.signal);
                        }}
                        title="Retry"
                    >
                        <RefreshCw size={15} />
                    </button>
                </div>
            ) : chartData.length === 0 ? (
                <div className="aet-activity-message">
                    No transaction activity found.
                </div>
            ) : (
                <div className="aet-activity-chart">
                    {chartData.map((item, index) => {
                        const count = Number(item.count || 0);
                        const barHeight =
                            count > 0
                                ? Math.max((count / maxValue) * 100, 5)
                                : 5;

                        return (
                            <div
                                className="aet-activity-column"
                                key={item.date || index}
                            >
                                <div className="aet-activity-value">
                                    {formatNumber(count)}
                                </div>

                                <div className="aet-activity-track">
                                    <div
                                        className="aet-activity-bar"
                                        style={{ height: `${barHeight}%` }}
                                    />
                                </div>

                                <span>{item.label}</span>
                            </div>
                        );
                    })}
                </div>
            )}
        </section>
    );
}
