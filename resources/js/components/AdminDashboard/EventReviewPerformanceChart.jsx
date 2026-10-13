
import { useEffect, useMemo, useState } from 'react';
import {
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
} from 'recharts';
import {
    Activity,
    CalendarDays,
    RefreshCw,
} from 'lucide-react';

const PERIODS = [
    { label: 'Day', value: 'day' },
    { label: 'Week', value: 'week' },
    { label: 'Month', value: 'month' },
    { label: 'Year', value: 'year' },
];

const STATUS_CONFIG = [
    { key: 'open', label: 'Open', color: '#64748b' },
    { key: 'pending', label: 'Pending', color: '#f59e0b' },
    { key: 'accepted', label: 'Accepted', color: '#16a34a' },
    { key: 'rejected', label: 'Rejected', color: '#dc2626' },
    { key: 'returned', label: 'Returned', color: '#8b5cf6' },
];

const EMPTY_STATUS = {
    open: 0,
    pending: 0,
    accepted: 0,
    rejected: 0,
    returned: 0,
};

export default function EventReviewPerformanceChart() {
    const [period, setPeriod] = useState('month');
    const [programs, setPrograms] = useState([]);
    const [selectedProgram, setSelectedProgram] = useState('all');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        const controller = new AbortController();

        async function fetchPerformance() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch(
                    `/admin/dashboard/event-review-performance?period=${period}`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        `Unable to load review performance (${response.status}).`
                    );
                }

                const result = await response.json();
                const programRows = Array.isArray(result.programs)
                    ? result.programs
                    : [];

                setPrograms(programRows);

                setSelectedProgram((current) => {
                    if (
                        current !== 'all' &&
                        !programRows.some(
                            (item) => item.program_code === current
                        )
                    ) {
                        return 'all';
                    }

                    return current;
                });
            } catch (err) {
                if (err.name !== 'AbortError') {
                    setError(err.message || 'Something went wrong.');
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }

        fetchPerformance();

        return () => controller.abort();
    }, [period]);

    const selectedPrograms = useMemo(() => {
        if (selectedProgram === 'all') {
            return programs;
        }

        return programs.filter(
            (item) => item.program_code === selectedProgram
        );
    }, [programs, selectedProgram]);

    const totals = useMemo(() => {
        return selectedPrograms.reduce(
            (result, program) => {
                STATUS_CONFIG.forEach(({ key }) => {
                    result[key] += Number(program[key] || 0);
                });

                result.total += Number(program.total || 0);

                return result;
            },
            { ...EMPTY_STATUS, total: 0 }
        );
    }, [selectedPrograms]);

    const chartData = STATUS_CONFIG
        .map((status) => ({
            ...status,
            value: totals[status.key],
        }))
        .filter((item) => item.value > 0);

    const formatNumber = (value) =>
        new Intl.NumberFormat('en-IN').format(value);

    return (
        <section className="admin-review-performance">
            <div className="admin-review-performance__header">
                <div className="admin-review-performance__heading">
                    <span className="admin-review-performance__icon">
                        <Activity size={19} />
                    </span>

                    <div>
                        <h3>Event Review Performance</h3>
                        <p>Program-wise review status overview</p>
                    </div>
                </div>

                <div className="admin-review-performance__toolbar">
                    <CalendarDays size={16} />

                    <div className="admin-review-performance__periods">
                        {PERIODS.map((item) => (
                            <button
                                key={item.value}
                                type="button"
                                className={
                                    period === item.value ? 'active' : ''
                                }
                                onClick={() => setPeriod(item.value)}
                                disabled={loading}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            <div className="admin-review-performance__filters">
                <label htmlFor="review-program-filter">
                    Select Program
                </label>

                <select
                    id="review-program-filter"
                    value={selectedProgram}
                    onChange={(event) =>
                        setSelectedProgram(event.target.value)
                    }
                >
                    <option value="all">All Programs</option>

                    {programs.map((program) => (
                        <option
                            key={program.program_code}
                            value={program.program_code}
                        >
                            {program.program_code}
                        </option>
                    ))}
                </select>

                <div className="admin-review-performance__total">
                    <span>Total Reviews</span>
                    <strong>{formatNumber(totals.total)}</strong>
                </div>
            </div>

            {error && (
                <div className="admin-review-performance__message error">
                    {error}
                    <button
                        type="button"
                        onClick={() => setPeriod((current) => current)}
                        title="Retry"
                    >
                        <RefreshCw size={15} />
                    </button>
                </div>
            )}

            {loading ? (
                <div className="admin-review-performance__message">
                    Loading review performance...
                </div>
            ) : !error && totals.total === 0 ? (
                <div className="admin-review-performance__message">
                    No review records found for this period.
                </div>
            ) : !error ? (
                <div className="admin-review-donut-layout">
                    <div className="admin-review-donut">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={chartData}
                                    dataKey="value"
                                    nameKey="label"
                                    cx="50%"
                                    cy="50%"
                                    innerRadius="67%"
                                    outerRadius="91%"
                                    paddingAngle={3}
                                    stroke="none"
                                >
                                    {chartData.map((item) => (
                                        <Cell
                                            key={item.key}
                                            fill={item.color}
                                        />
                                    ))}
                                </Pie>

                                <Tooltip
                                    formatter={(value, name) => [
                                        formatNumber(Number(value)),
                                        name,
                                    ]}
                                />
                            </PieChart>
                        </ResponsiveContainer>

                        <div className="admin-review-donut__center">
                            <strong>{formatNumber(totals.total)}</strong>
                            <span>Total Reviews</span>
                        </div>
                    </div>

                    <div className="admin-review-legend">
                        {STATUS_CONFIG.map((status) => {
                            const count = totals[status.key];
                            const percentage = totals.total
                                ? ((count / totals.total) * 100).toFixed(1)
                                : '0.0';

                            return (
                                <div
                                    className="admin-review-legend__item"
                                    key={status.key}
                                >
                                    <span
                                        className="admin-review-legend__dot"
                                        style={{
                                            backgroundColor: status.color,
                                        }}
                                    />

                                    <span className="admin-review-legend__label">
                                        {status.label}
                                    </span>

                                    <strong>
                                        {formatNumber(count)}
                                    </strong>

                                    <span className="admin-review-legend__percentage">
                                        {percentage}%
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            ) : null}
        </section>
    );
}
