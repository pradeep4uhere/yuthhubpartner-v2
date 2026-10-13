import { useEffect, useState } from 'react';
import { PieChart } from 'lucide-react';

const statusColors = {
    open: '#64748b',
    accepted: '#16a34a',
    pending: '#eab308',
    rejected: '#dc2626',
    return: '#8b5cf6',
    unknown: '#94a3b8',
};

export default function EventTransactionPieChart() {
    const [data, setData] = useState([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        fetch('/admin/dashboard/event-transaction-status', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Unable to load transaction statistics.');
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

    const gradient = data.length
        ? data.map((item) => {
            const start = cumulative;
            cumulative += Number(item.percentage) || 0;

            const color =
                statusColors[String(item.status).toLowerCase()] ??
                statusColors.unknown;

            return `${color} ${start}% ${cumulative}%`;
        }).join(', ')
        : '#e5e7eb 0% 100%';

    return (
        <article className="admin-panel admin-transaction-status">
            <div className="admin-panel__heading">
                <div>
                    <h2>Event transaction status</h2>
                    <p>Overall review status distribution</p>
                </div>
                <PieChart size={20} />
            </div>

            {loading ? (
                <div className="admin-chart-empty">
                    Loading transaction statistics...
                </div>
            ) : error ? (
                <div className="admin-chart-empty" role="alert">
                    {error}
                </div>
            ) : (
                <>
                    <div className="admin-status-pie-layout">
                        <div
                            className="admin-status-pie"
                            style={{ background: `conic-gradient(${gradient})` }}
                            role="img"
                            aria-label={`Pie chart showing ${total} event transactions by status`}
                        >
                            <div className="admin-status-pie__center">
                                <strong>{total.toLocaleString('en-IN')}</strong>
                                <span>Total</span>
                            </div>
                        </div>

                        <div className="admin-status-legend">
                            {data.map((item) => {
                                const color =
                                    statusColors[String(item.status).toLowerCase()] ??
                                    statusColors.unknown;

                                return (
                                    <div
                                        className="admin-status-legend__item"
                                        key={item.status}
                                    >
                                        <span
                                            className="admin-status-legend__dot"
                                            style={{ backgroundColor: color }}
                                        />

                                        <span className="admin-status-legend__name">
                                            {item.status}
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

                    {data.length === 0 && (
                        <p className="admin-chart-empty">
                            No event transactions found.
                        </p>
                    )}
                </>
            )}
        </article>
    );
}
