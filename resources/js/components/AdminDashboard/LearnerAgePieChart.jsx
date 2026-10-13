import { useEffect, useState } from 'react';
import { PieChart } from 'lucide-react';

const ageColors = {
    'Below 18': '#94a3b8',
    '18-24': '#4776e6',
    '25-34': '#20a486',
    '35-44': '#e879b9',
    '45+': '#f59e0b',
    Unknown: '#64748b',
};

export default function LearnerAgePieChart() {
    const [data, setData] = useState([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        fetch('/admin/dashboard/learner-age-distribution', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(
                        `Unable to load age statistics (HTTP ${response.status}).`
                    );
                }

                return response.json();
            })
            .then((result) => {
                setData(result.data ?? []);
                setTotal(result.total ?? 0);
            })
            .catch((err) => {
                console.error('Learner age API error:', err);
                setError(err.message);
            })
            .finally(() => setLoading(false));
    }, []);

    let cumulative = 0;

    const gradient = data.map((item) => {
        const start = cumulative;
        const share = total > 0 ? (item.count / total) * 100 : 0;
        cumulative += share;

        const color = ageColors[item.age_group] ?? '#64748b';

        return `${color} ${start}% ${cumulative}%`;
    }).join(', ');

    return (
        <article className="admin-panel admin-transaction-status">
            <div className="admin-panel__heading">
                <div>
                    <h2>Learner age distribution</h2>
                    <p>Overall registered learners by age group</p>
                </div>

                <PieChart size={20} />
            </div>

            {loading ? (
                <div className="admin-chart-empty">
                    Loading age statistics...
                </div>
            ) : error ? (
                <div className="admin-chart-empty" role="alert">
                    {error}
                </div>
            ) : data.length === 0 ? (
                <div className="admin-chart-empty">
                    No learner data available.
                </div>
            ) : (
                <div className="admin-status-pie-layout">
                    <div
                        className="admin-status-pie"
                        style={{
                            background: `conic-gradient(${gradient || '#e5e7eb 0% 100%'})`,
                        }}
                        role="img"
                        aria-label={`Age distribution of ${total} learners`}
                    >
                        <div className="admin-status-pie__center">
                            <strong>{total.toLocaleString('en-IN')}</strong>
                            <span>Total learners</span>
                        </div>
                    </div>

                    <div className="admin-status-legend">
                        {data.map((item) => (
                            <div
                                className="admin-status-legend__item"
                                key={item.age_group}
                            >
                                <span
                                    className="admin-status-legend__dot"
                                    style={{
                                        backgroundColor:
                                            ageColors[item.age_group] ?? '#64748b',
                                    }}
                                />

                                <span className="admin-status-legend__name">
                                    {item.age_group}
                                </span>

                                <strong>
                                    {Number(item.count).toLocaleString('en-IN')}
                                </strong>

                                <small>{item.percentage}%</small>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </article>
    );
}
