import { ArrowUpRight, ChartNoAxesCombined } from 'lucide-react';

const genderConfig = [
    { key: 'male', label: 'Male', className: 'male' },
    { key: 'female', label: 'Female', className: 'female' },
    { key: 'other', label: 'Other', className: 'other' },
];

export default function LearnerOutcomes({
    monthlyGenderData = [],
    loading = false,
}) {
    const maxCount = Math.max(
        1,
        ...monthlyGenderData.flatMap((month) =>
            genderConfig.map(({ key }) => Number(month[key]) || 0)
        )
    );

    const chartMax = Math.ceil(maxCount / 100) * 100 || 100;

    const ticks = [
        chartMax,
        chartMax * 0.75,
        chartMax * 0.5,
        chartMax * 0.25,
        0,
    ];

    return (
        <article className="admin-panel admin-outcomes" id="placements">
            <div className="admin-panel__heading">
                <div>
                    <h2>Monthly learner gender</h2>
                    <p>Month-wise learner registration by gender</p>
                </div>
            </div>

            <div className="admin-chart-legend">
                {genderConfig.map(({ key, label, className }) => (
                    <span key={key}>
                        <i className={`legend-dot legend-dot--${className}`} />
                        {label}
                    </span>
                ))}
            </div>

            {loading ? (
                <div className="admin-chart-empty">
                    Loading learner statistics...
                </div>
            ) : monthlyGenderData.length === 0 ? (
                <div className="admin-chart-empty">
                    No learner data available for this period.
                </div>
            ) : (
                <div
                    className="admin-chart"
                    role="img"
                    aria-label="Monthly learner registration counts by gender"
                >
                    <div className="admin-chart__axis">
                        {ticks.map((tick, index) => (
                            <span key={index}>
                                {Math.round(tick).toLocaleString('en-IN')}
                            </span>
                        ))}
                    </div>

                    <div className="admin-chart__plot">
                        {ticks.map((tick, index) => (
                            <div
                                className="admin-chart__gridline"
                                style={{
                                    bottom: `${(index / (ticks.length - 1)) * 100}%`,
                                }}
                                key={index}
                            />
                        ))}

                        <div className="admin-chart__columns">
                            {monthlyGenderData.map((month) => (
                                <div
                                    className="admin-chart__month"
                                    key={`${month.year}-${month.month}`}
                                >
                                    <div className="admin-chart__bars">
                                        {genderConfig.map(({ key, className }) => {
                                            const count = Number(month[key]) || 0;

                                            return (
                                                <span
                                                    key={key}
                                                    className={`admin-chart__bar admin-chart__bar--${className}`}
                                                    style={{
                                                        height: `${(count / chartMax) * 100}%`,
                                                    }}
                                                    title={`${month.label}: ${key} - ${count}`}
                                                />
                                            );
                                        })}
                                    </div>

                                    <span>{month.label}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            <div className="admin-outcomes__footer">
                <span>
                    <ChartNoAxesCombined size={16} />
                    <b>{monthlyGenderData.length}</b> months displayed
                </span>

                <button
                    type="button"
                    onClick={() =>
                        window.alert('Detailed gender report is coming soon.')
                    }
                >
                    View report <ArrowUpRight size={14} />
                </button>
            </div>
        </article>
    );
}
