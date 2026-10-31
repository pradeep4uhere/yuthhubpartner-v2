import React from "react";
import "./ComingSoon.css";

/**
 * Displays a reusable Coming Soon placeholder for dashboard sections.
 */
export default function ComingSoon({ title = "This Feature", description }) {
    // Build a section-specific description when none is provided.
    const sectionDescription =
        description ||
        `We're building a better experience for your ${title.toLowerCase()}. This section is under development and will be available soon.`;

    return (
        <div className="yp-content">
            <section className="yp-coming-soon">
                <div className="yp-coming-soon__visual">
                    <div className="yp-coming-soon__orb">
                        <span className="yp-coming-soon__icon">✦</span>
                    </div>

                    <span className="yp-coming-soon__spark yp-spark-one">
                        ✧
                    </span>
                    <span className="yp-coming-soon__spark yp-spark-two">
                        ✦
                    </span>
                    <span className="yp-coming-soon__spark yp-spark-three">
                        ✧
                    </span>
                </div>

                <span className="yp-coming-soon__badge">
                    <span className="yp-coming-soon__dot" />
                    UNDER DEVELOPMENT
                </span>

                <h2 className="yp-coming-soon__title">
                    Something Great Is <span>Coming Soon</span>
                </h2>

                <p className="yp-coming-soon__description">
                    {sectionDescription}
                </p>

                <div className="yp-coming-soon__progress">
                    <div className="yp-coming-soon__progress-track">
                        <div className="yp-coming-soon__progress-fill" />
                    </div>

                    <span>Work in progress</span>
                </div>

                <div className="yp-coming-soon__footer">
                    <span className="yp-coming-soon__footer-icon">✓</span>
                    Better tools. Smarter workflows. One platform.
                </div>
            </section>
        </div>
    );
}
