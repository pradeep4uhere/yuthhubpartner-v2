
import {
    ArrowDownRight,
    ArrowUpRight,
    MoreHorizontal,
} from 'lucide-react';

export default function MetricCard({
    label,
    value,
    change = '',
    direction = 'up',
    icon: Icon,
    tone,
    detail,
}) {
    const TrendIcon =
        direction === 'down' ? ArrowDownRight : ArrowUpRight;

    return (
        <article className="admin-metric">
            <div className="admin-metric__top">
                <span className={`admin-metric__icon admin-metric__icon--${tone}`}>
                    <Icon size={18} strokeWidth={1.8} />
                </span>

                <button
                    className="admin-icon-button admin-metric__more"
                    aria-label={`${label} options`}
                    title="More options"
                >
                    <MoreHorizontal size={18} />
                </button>
            </div>

            <p className="admin-metric__label">{label}</p>

            <div className="admin-metric__value-row">
                <strong>{value}</strong>

                {change !== '' && (
                    <span className={`admin-trend admin-trend--${direction}`}>
                        <TrendIcon size={14} />
                        {change}
                    </span>
                )}
            </div>

            <p className="admin-metric__detail">{detail}</p>
        </article>
    );
}
