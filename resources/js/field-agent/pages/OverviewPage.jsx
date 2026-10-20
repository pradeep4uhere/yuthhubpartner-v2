
import React from 'react';

/**
 * OverviewPage Component
 *
 * Displays a personalized welcome section for the Field Agent dashboard.
 *
 * Props:
 * @param {string} name - Field agent's display name.
 * @param {string} cscId - Field agent's CSC ID.
 * @param {string} greeting - Greeting text displayed before the name.
 * @param {string} description - Welcome message displayed below the heading.
 *
 * Uses fallback values when optional props are not provided.
 */
const OverviewPage = ({
    name = 'Field Agent',
    sakhiid = 'Not Assigned',
    contactNumber = 'Contact Number',
    greeting,
    description = "Welcome back! Here's your dashboard overview.",
}) => {
    /**
     * Returns a greeting based on the current local time when no
     * greeting prop is provided by the parent component.
     */
    const getGreeting = () => {
        if (greeting) {
            return greeting;
        }

        const hour = new Date().getHours();

        if (hour < 12) return 'Good Morning';
        if (hour < 17) return 'Good Afternoon';

        return 'Good Evening';
    };

    return (
        <section className="welcomeSection">
            <div className="welcomeContent">
                <div className="welcomeIcon">
                    <span role="img" aria-label="Welcome">
                        👋
                    </span>
                </div>

                <div className="welcomeText">
                    <span className="welcomeEyebrow">
                        FIELD AGENT DASHBOARD
                    </span>

                    <h2>
                        {getGreeting()},{' '}
                        <span className="welcomeName">{name}</span>
                    </h2>

                    <p>{description}</p>

                    <div className="welcomeMeta">
                        <span className="welcomeStatusDot"></span>
                        <span>Agent ID</span>
                        <strong>{sakhiid}</strong>
                    </div>
                    <div className="welcomeMeta">
                        <span className="welcomeStatusDot"></span>
                        <span>Mobile</span>
                        <strong>{contactNumber}</strong>
                    </div>
                </div>
            </div>

            <div className="welcomeDecoration" aria-hidden="true">
                ✦
            </div>
        </section>
    );
};

export default OverviewPage;
