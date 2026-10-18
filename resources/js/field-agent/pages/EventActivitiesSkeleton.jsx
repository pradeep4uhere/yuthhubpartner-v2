import React from 'react';
import './EventActivitiesPage.css';

export default function EventActivitiesSkeleton() {
    return (
        <section className="ea-page ea-skeleton-page">
            <header className="ea-header">
                <div className="ea-skeleton-heading">
                    <div className="ea-skeleton-line ea-skeleton-eyebrow" />
                    <div className="ea-skeleton-line ea-skeleton-title" />
                    <div className="ea-skeleton-line ea-skeleton-subtitle" />
                </div>

                <div className="ea-skeleton-button" />
            </header>

            <div className="ea-summary-grid">
                {[1, 2, 3, 4, 5].map((item) => (
                    <div className="ea-skeleton-summary" key={item}>
                        <div className="ea-skeleton-icon" />
                        <div className="ea-skeleton-summary-content">
                            <div className="ea-skeleton-line ea-skeleton-label" />
                            <div className="ea-skeleton-line ea-skeleton-count" />
                        </div>
                    </div>
                ))}
            </div>

            <div className="ea-skeleton-filters">
                <div className="ea-skeleton-input" />
                <div className="ea-skeleton-input" />
                <div className="ea-skeleton-input" />
            </div>

            <div className="ea-skeleton-list">
                {[1, 2, 3].map((item) => (
                    <article className="ea-skeleton-card" key={item}>
                        <div className="ea-skeleton-card-header">
                            <div>
                                <div className="ea-skeleton-line ea-skeleton-record" />
                                <div className="ea-skeleton-line ea-skeleton-name" />
                            </div>
                            <div className="ea-skeleton-status" />
                        </div>

                        <div className="ea-skeleton-details">
                            {[1, 2, 3, 4].map((detail) => (
                                <div key={detail}>
                                    <div className="ea-skeleton-line ea-skeleton-label" />
                                    <div className="ea-skeleton-line ea-skeleton-value" />
                                </div>
                            ))}
                        </div>

                        <div className="ea-skeleton-card-footer">
                            <div className="ea-skeleton-line ea-skeleton-footer-text" />
                            <div className="ea-skeleton-button" />
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}

