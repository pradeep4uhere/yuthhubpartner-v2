import { useEffect, useState } from 'react';
import { PieChart } from 'lucide-react';

const genderColors = {
    male: '#4776e6',
    female: '#e879b9',
    other: '#20a486',
    unknown: '#94a3b8',
};

export default function LearnerGenderPieChart() {``
    const [data, setData] = useState([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        fetch('/admin/dashboard/learner-gender-status', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Unable to load learner gender statistics.');
                }

                return response.json();
            })
            .then((result) => {
                setData(result.data ?? []);
                setTotal(result.total ?? 0);
            })
            .catch((err) => setError(err.message))
            .finally(() => setLoading(false));
    }, []);

    let cumulative = 0;

    const gradient = data.map((item) => {
        const start = cumulative;
        cumulative += Number(item.percentage) || 0;

        const color =
            genderColors[String(item.gender).toLowerCase()] ??
            genderColors.unknown;

        return `${color} ${start}% ${cumulative}%`;
    }).join(', ');

    return (
        <article className="admin-panel admin-transaction-status">
            <div className="admin-panel__heading">
                <div>
                    <h2>Learner gender distribution</h2>
                    <p>Overall registered learners by gender</p>
                </div>

                <PieChart size={20} />
            </div>

            {loading ? (
                <div className="admin-chart-empty">
                    Loading learner statistics...
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
                        aria-label={`Gender distribution of ${total} learners`}
                    >
                        <div className="admin-status-pie__center">
                            <strong>{total.toLocaleString('en-IN')}</strong>
                            <span>Total learners</span>
                        </div>
                    </div>

                    <div className="admin-status-legend">
                        {data.map((item) => {
                            const color =
                                genderColors[String(item.gender).toLowerCase()] ??
                                genderColors.unknown;

                            return (
                                <div
                                    className="admin-status-legend__item"
                                    key={item.gender}
                                >
                                    <span
                                        className="admin-status-legend__dot"
                                        style={{ backgroundColor: color }}
                                    />

                                    <span className="admin-status-legend__name">
                                        {item.gender}
                                    </span>

                                    <strong>
                                        {Number(item.count).toLocaleString('en-IN')}
                                    </strong>

                                    <small>{item.percentage}%</small>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </article>
    );
}
