import React from 'react';
import './DashboardSkeleton.css';

export default function DashboardSkeleton() {
    return (
        <div className="fa-dashboard-skeleton">
            {/* Summary cards */}
            <div className="fa-ds-summary-grid">
                {[1, 2, 3, 4].map((item) => (
                    <div className="fa-ds-summary-card" key={item}>
                        <div className="fa-ds-row">
                            <div className="fa-ds-icon fa-ds-shimmer" />
                            <div className="fa-ds-small-line fa-ds-shimmer" />
                        </div>

                        <div className="fa-ds-label fa-ds-shimmer" />
                        <div className="fa-ds-value fa-ds-shimmer" />
                        <div className="fa-ds-description fa-ds-shimmer" />
                    </div>
                ))}
            </div>

            {/* Analytics heading */}
            <section className="fa-ds-analytics">
                <div className="fa-ds-analytics-heading">
                    <div>
                        <div className="fa-ds-eyebrow fa-ds-shimmer" />
                        <div className="fa-ds-title fa-ds-shimmer" />
                        <div className="fa-ds-description fa-ds-shimmer" />
                    </div>

                    <div className="fa-ds-period fa-ds-shimmer" />
                </div>

                {/* Charts */}
                <div className="fa-ds-chart-grid">
                    <div className="fa-ds-chart-card fa-ds-chart-card--wide">
                        <div className="fa-ds-chart-heading fa-ds-shimmer" />
                        <div className="fa-ds-chart fa-ds-shimmer" />
                    </div>

                    <div className="fa-ds-chart-card">
                        <div className="fa-ds-chart-heading fa-ds-shimmer" />
                        <div className="fa-ds-donut-wrap">
                            <div className="fa-ds-donut fa-ds-shimmer" />
                        </div>
                    </div>

                    <div className="fa-ds-chart-card">
                        <div className="fa-ds-chart-heading fa-ds-shimmer" />
                        <div className="fa-ds-chart fa-ds-shimmer" />
                    </div>
                </div>
            </section>

            {/* Profile section */}
            <section className="fa-ds-profile">
                <div className="fa-ds-profile-title fa-ds-shimmer" />
                <div className="fa-ds-profile-grid">
                    {[1, 2, 3, 4, 5, 6].map((item) => (
                        <div className="fa-ds-profile-item" key={item}>
                            <div className="fa-ds-icon fa-ds-shimmer" />
                            <div className="fa-ds-profile-text">
                                <div className="fa-ds-label fa-ds-shimmer" />
                                <div className="fa-ds-value fa-ds-shimmer" />
                            </div>
                        </div>
                    ))}
                </div>
            </section>
        </div>
    );
}
