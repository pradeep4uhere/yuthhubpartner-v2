import { useEffect, useState } from "react";
import { Clock3, Activity } from "lucide-react";

/**
 * Displays the latest partner activities.
 * Fetches recent activities from the Laravel API.
 */
export default function RecentActivities({ onNavigate }) {
    const [activities, setActivities] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    /**
     * Fetch recent activities when the component mounts.
     */
    useEffect(() => {
        const controller = new AbortController();

        /**
         * Retrieves recent activities from the partner API.
         */
        const fetchActivities = async () => {
            setLoading(true);
            setError("");

            try {
                const response = await fetch(
                    "/api/partner/dashboard/recent-activities",
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
                    throw new Error("Failed to fetch activities");
                }

                const result = await response.json();

                setActivities(
                    Array.isArray(result.activities) ? result.activities : [],
                );
            } catch (err) {
                if (err.name === "AbortError") return;

                console.error("Recent Activities Error:", err);

                setError("Unable to load recent activities.");
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        };

        fetchActivities();

        return () => controller.abort();
    }, []);

    return (
        <section className="yp-panel yp-activities">
            <div className="yp-panel-heading">
                <h2>
                    <Clock3 size={21} />
                    Recent Activities
                </h2>

                <button
                    type="button"
                    className="yp-link"
                    onClick={() => onNavigate("transactions")}
                >
                    View All
                </button>
            </div>

            {loading ? (
                <div className="yp-empty">Loading recent activities...</div>
            ) : error ? (
                <div className="yp-empty">{error}</div>
            ) : activities.length ? (
                <div className="yp-activity-list">
                    {activities.map((a, i) => (
                        <div className="yp-activity" key={a.id || i}>
                            <span className="yp-activity-icon">
                                <Activity size={21} />
                            </span>

                            <div>
                                <strong>{a.title}</strong>
                                <small>{a.description}</small>
                            </div>

                            <time>{a.time_label || ""}</time>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="yp-empty">
                    No recent activity available yet.
                </div>
            )}
        </section>
    );
}
