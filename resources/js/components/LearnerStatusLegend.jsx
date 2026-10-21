import {
    Info,
    CircleDot,
    BriefcaseBusiness,
    GraduationCap,
    ShieldCheck,
} from "lucide-react";

import "./LearnerStatusLegend.css";

const STATUSES = [
    {
        key: "action_required",
        className: "action-required",
        fallback: "Action Required",
    },
    {
        key: "submitted",
        className: "submitted",
        fallback: "Submitted",
    },
    {
        key: "accepted",
        className: "accepted",
        fallback: "Accepted",
    },
    {
        key: "rejected",
        className: "rejected",
        fallback: "Rejected",
    },
];

const ACTIVITIES = [
    {
        key: "first_skills",
        fallback: "1. Skills",
        icon: GraduationCap,
        number: "01",
    },
    {
        key: "second_job",
        fallback: "2. Job",
        icon: BriefcaseBusiness,
        number: "02",
    },
    {
        key: "third_social_protection",
        fallback: "3. Social Protection",
        icon: ShieldCheck,
        number: "03",
    },
];

export default function LearnerStatusLegend({
    t,
    title = "Status Guide",
    className = "",
    compact = false,
}) {
    const translate = (key, fallback) => {
        if (typeof t !== "function") return fallback;

        const translated = t(key);

        return translated && translated !== key ? translated : fallback;
    };

    return (
        <section
            className={`lsl-legend ${compact ? "lsl-legend--compact" : ""} ${className}`}
            aria-label={title}
        >
            <div className="lsl-header">
                <div className="lsl-header-left">
                    <span className="lsl-header-icon">
                        <Info size={17} />
                    </span>

                    <div>
                        <h3>{title}</h3>
                        <p>Understand learner activity indicators</p>
                    </div>
                </div>

                <span className="lsl-header-badge">
                    <CircleDot size={13} />
                    Legend
                </span>
            </div>

            <div className="lsl-body">
                {/* Status indicators */}

                <div className="lsl-statuses">
                    <span className="lsl-section-label">STATUS INDICATORS</span>

                    <div className="lsl-status-list">
                        {STATUSES.map((status) => (
                            <span
                                key={status.key}
                                className={`lsl-status lsl-status--${status.className}`}
                            >
                                <span className="lsl-dot" />

                                {translate(status.key, status.fallback)}
                            </span>
                        ))}
                    </div>
                </div>

                <span className="lsl-divider" aria-hidden="true" />

                {/* Activity indicators */}

                <div className="lsl-activities">
                    <span className="lsl-section-label">ACTIVITY ORDER</span>

                    <div className="lsl-activity-list">
                        {ACTIVITIES.map((activity) => {
                            const Icon = activity.icon;

                            return (
                                <div
                                    className="lsl-activity"
                                    key={activity.key}
                                >
                                    <span className="lsl-activity-icon">
                                        <Icon size={15} />
                                    </span>

                                    <span className="lsl-activity-name">
                                        {translate(
                                            activity.key,
                                            activity.fallback,
                                        )}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}
