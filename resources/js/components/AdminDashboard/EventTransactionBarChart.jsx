
import { useEffect, useState } from 'react';
import {
    LineChart,
    Line,
    CartesianGrid,
    XAxis,
    YAxis,
    Tooltip,
    ResponsiveContainer,
} from 'recharts';
import { Activity, CalendarDays, RefreshCw } from 'lucide-react';

const periodOptions = [
    { label: 'Day-wise', value: 'day' },
    { label: 'Week-wise', value: 'week' },
    { label: 'Month-wise', value: 'month' },
];

const formatNumber = (value) =>
    Number(value ?? 0).toLocaleString('en-IN');

function TransactionTooltip({ active, payload, label }) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="admin-transaction-tooltip">
            <span className="admin-transaction-tooltip__label">
                {label}
            </span>

            <div className="admin-transaction-tooltip__value">
                <span className="admin-transaction-tooltip__dot" />
                <span>Transactions</span>
                <strong>{formatNumber(payload[0].value)}</strong>
            </div>
        </div>
    );
}

export default function EventTransactionBarChart() {
    const [period, setPeriod] = useState('day');
    const [data, setData] = useState([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        const controller = new AbortController();

        setLoading(true);
        setError('');

        fetch(
            `/admin/dashboard/event-transaction-counts?period=${period}`,
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            }
        )
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(
                        `Unable to load transaction counts (HTTP ${response.status}).`
                    );
                }

                return response.json();
            })
            .then((result) => {
                setData(
                    (result.data ?? []).map((item) => ({
                        ...item,
                        count: Number(item.count ?? 0),
                    }))
                );

                setTotal(Number(result.total ?? 0));
            })
            .catch((err) => {
                if (err.name !== 'AbortError') {
                    console.error(
                        'Event transaction chart error:',
                        err
                    );
                    setError(err.message);
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            });

        return () => controller.abort();
    }, [period]);

    return (
        <article className="admin-panel admin-transaction-bar-chart">
            <div className="admin-panel__heading">
                <div>
                    <h2>Event transactions</h2>
                    <p>Transaction volume over time</p>
                </div>

                <Activity size={20} />
            </div>

            <div className="admin-transaction-chart-toolbar">
                <div className="admin-transaction-chart-total">
                    <span>Total in selected period</span>
                    <strong>{formatNumber(total)}</strong>
                </div>

                <label className="admin-select-wrap">
                    <CalendarDays size={15} />

                    <select
                        value={period}
                        onChange={(event) =>
                            setPeriod(event.target.value)
                        }
                        aria-label="Transaction chart period"
                    >
                        {periodOptions.map((option) => (
                            <option
                                key={option.value}
                                value={option.value}
                            >
                                {option.label}
                            </option>
                        ))}
                    </select>
                </label>
            </div>

            {loading ? (
                <div className="admin-chart-empty">
                    <RefreshCw
                        size={16}
                        className="admin-chart-spinner"
                    />
                    Loading transaction data...
                </div>
            ) : error ? (
                <div className="admin-chart-empty" role="alert">
                    {error}
                </div>
            ) : data.length === 0 ? (
                <div className="admin-chart-empty">
                    No transaction data available for this period.
                </div>
            ) : (
                <div className="admin-transaction-recharts">
                    <ResponsiveContainer width="100%" height={310}>
                        <LineChart
                            data={data}
                            margin={{
                                top: 18,
                                right: 18,
                                left: 4,
                                bottom: 8,
                            }}
                        >
                            <CartesianGrid
                                stroke="#edf1f7"
                                strokeDasharray="4 5"
                                vertical={false}
                            />

                            <XAxis
                                dataKey="label"
                                axisLine={false}
                                tickLine={false}
                                tick={{
                                    fill: '#8490a4',
                                    fontSize: 11,
                                }}
                                tickMargin={12}
                                minTickGap={12}
                                interval={
                                    period === 'day'
                                        ? 'preserveStartEnd'
                                        : 0
                                }
                            />

                            <YAxis
                                axisLine={false}
                                tickLine={false}
                                tick={{
                                    fill: '#8490a4',
                                    fontSize: 11,
                                }}
                                tickFormatter={formatNumber}
                                allowDecimals={false}
                                width={48}
                                domain={[0, 'auto']}
                            />

                            <Tooltip
                                content={<TransactionTooltip />}
                                cursor={{
                                    stroke: '#4776e6',
                                    strokeDasharray: '4 4',
                                }}
                            />

                            <Line
                                type="monotone"
                                dataKey="count"
                                name="Transactions"
                                stroke="#4776e6"
                                strokeWidth={3}
                                dot={{
                                    r: 3,
                                    fill: '#ffffff',
                                    stroke: '#4776e6',
                                    strokeWidth: 2,
                                }}
                                activeDot={{
                                    r: 6,
                                    fill: '#315fc9',
                                    stroke: '#ffffff',
                                    strokeWidth: 3,
                                }}
                                connectNulls={false}
                                animationDuration={700}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            )}

            {!loading && !error && data.length > 0 && (
                <div className="admin-transaction-bar-chart__footer">
                    {period === 'day'
                        ? 'Daily counts · Last 30 days'
                        : period === 'week'
                            ? 'Weekly counts · Last 12 weeks'
                            : 'Monthly counts · Last 12 months'}
                </div>
            )}
        </article>
    );
}
