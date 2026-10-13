
import { useEffect, useState } from 'react';

const COLORS = [
    '#4776e6',
    '#20a486',
    '#e879b9',
    '#f59e0b',
    '#8b5cf6',
    '#ef6461',
    '#0891b2',
    '#84a34a',
    '#94a3b8',
];

export default function LearnersByProgrammeChart() {
    const [data, setData] = useState([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        const controller = new AbortController();

        fetch('/admin/dashboard/learners-by-programme', {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(
                        `Unable to load programme statistics (HTTP ${response.status}).`
                    );
                }

                return response.json();
            })
            .then((result) => {
                if (result.success !== true) {
                    throw new Error('Unable to load programme statistics.');
                }

                setData(result.data ?? []);
                setTotal(Number(result.total ?? 0));
            })
            .catch((err) => {
                if (err.name !== 'AbortError') {
                    console.error('Learners by programme:', err);
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

    let cumulative = 0;

    const gradient = data.length
        ? `conic-gradient(${data.map((item, index) => {
            const start = cumulative;
            cumulative += total > 0
                ? (Number(item.count) / total) * 100
                : 0;

            return `${COLORS[index % COLORS.length]} ${start}% ${cumulative}%`;
        }).join(', ')})`
        : '#e5e7eb';

    return (
        <section className="admin-programme-chart">
            <div className="admin-programme-chart__heading">
                <div>
                    <h2>Learners by Programme</h2>
                    <p>Distribution of registered learners</p>
                </div>
                <span className="admin-programme-chart__badge">
                    {data.length} programmes
                </span>
            </div>

            {loading ? (
                <div className="admin-programme-chart__message">
                    Loading programme statistics...
                </div>
            ) : error ? (
                <div
                    className="admin-programme-chart__message admin-programme-chart__error"
                    role="alert"
                >
                    {error}
                </div>
            ) : total === 0 ? (
                <div className="admin-programme-chart__message">
                    No learner data available.
                </div>
            ) : (
                <div className="admin-programme-chart__content">
                    <div
                        className="admin-programme-chart__donut"
                        style={{ background: gradient }}
                        role="img"
                        aria-label={`Total ${total} learners distributed across ${data.length} programmes`}
                    >
                        <div className="admin-programme-chart__centre">
                            <span>Total learners</span>
                            <strong>{total.toLocaleString('en-IN')}</strong>
                        </div>
                    </div>

                    <div className="admin-programme-chart__legend">
                        {data.map((item, index) => (
                            <div
                                className="admin-programme-chart__item"
                                key={item.programme}
                            >
                                <span
                                    className="admin-programme-chart__dot"
                                    style={{
                                        backgroundColor:
                                            COLORS[index % COLORS.length],
                                    }}
                                />

                                <div className="admin-programme-chart__details">
                                    <span
                                        className="admin-programme-chart__name"
                                        title={item.programme}
                                    >
                                        {item.programme}
                                    </span>
                                    <span className="admin-programme-chart__count">
                                        {Number(item.count).toLocaleString('en-IN')}
                                        {' · '}
                                        {Number(item.percentage).toFixed(1)}%
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}
