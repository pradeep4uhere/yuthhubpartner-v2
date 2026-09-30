export default function PartnerWorkspace({ partnerName }) {
    return (
        <main className="partner-home">
            <div className="partner-home__container">

                {/* Logo */}
                <div className="partner-home__logo">
                    <a href="/" aria-label="YouthHub Partner home">
                        <img
                            src="/images/youthhub-logo.png"
                            alt="YouthHub"
                        />
                    </a>
                </div>

                {/* Platform Badge */}
                <div className="platform-badge">
                    <span className="platform-badge__star">★</span>
                    <span>YuWaah Sakhi Platform</span>
                </div>

                {/* Title */}
                <h1 className="partner-home__title">
                    YuWaah Partner App
                </h1>

                {/* Main Information Card */}
                <section className="partner-info-card">

                    <p className="partner-info-card__label">
                        YuWaah Partner App
                    </p>

                    <p>
                        The <strong>YuWaah Partner App</strong> is the official
                        platform for field and implementation partners of the
                        <strong> Digital Girls Hub</strong> initiative,
                        developed by <strong>UNICEF YuWaah</strong> to
                        accelerate skilling, employment, and social protection
                        outcomes for young women (18–29 years) across India.
                    </p>

                    <p>
                        Designed for CSCs, VLEs, and implementation partners,
                        the app enables seamless mobilisation, learner
                        onboarding, progress tracking, placement coordination,
                        and verification through the YouthHub ecosystem.
                    </p>

                    <p className="features-title">
                        Key Features of the YuWaah Partner App
                    </p>

                    <ul className="feature-list">
                        <li>
                            Information on learners mobilised and onboarded
                            via YouthHub pathways
                        </li>

                        <li>
                            Create and manage skilling and outreach events
                        </li>

                        <li>
                            Track learner progress, course completion, and
                            certification
                        </li>

                        <li>
                            Support job matching, interviews, and placement
                            workflows
                        </li>

                        <li>
                            Upload and manage verification and social
                            protection documents
                        </li>

                        <li>
                            Monitor real-time status of learner applications
                            and outcomes
                        </li>
                    </ul>

                    <p>
                        By connecting last-mile partners to a unified digital
                        system, the YuWaah Partner App ensures every girl
                        receives the right support at the right stage of her
                        journey — from mobilisation and skilling to employment
                        and social protection.
                    </p>

                    {partnerName && (
                        <div className="partner-welcome">
                            <span className="partner-welcome__icon">✓</span>
                            <div>
                                <small>Welcome</small>
                                <strong>{partnerName}</strong>
                            </div>
                        </div>
                    )}

                </section>

                {/* Footer */}
                <footer className="partner-home__footer">
                    <span>YOUTHHUB PARTNER</span>
                    <span>Powered by YouthHub</span>
                </footer>

            </div>
        </main>
    );
}
