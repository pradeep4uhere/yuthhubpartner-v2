export default function PartnerWorkspace({ partnerName }) {
    return (
        <main className="workspace">
            <header className="workspace__header">
                <a
                    className="brand"
                    href="/"
                    aria-label="YouthHub Partner home"
                >
                    <span className="brand__mark" aria-hidden="true">
                        <img src="icon.png" alt="YouthHub Partner" />
                    </span>

                    <span>
                        YouthHub <strong>Partner</strong>
                    </span>
                </a>

                <span className="workspace__status">
                    Youthhub Partner Platform
                </span>
            </header>

            <section
                className="welcome"
                aria-labelledby="welcome-title"
            >
                <p className="eyebrow logo-wrapper">
                   <span className="logo__mark" aria-hidden="true">
                        <img src="logo.png" alt="YouthHub Partner" />
                    </span>
                </p>

                <h1 id="welcome-title">
                    Youthhub Partner App
                </h1>

                <div className="welcome__rule" aria-hidden="true" />

                <div className="welcome__content">

                    <p className="welcome__copy">
                        The <strong>Youthhub Partner App</strong> is the
                        official platform for field and implementation
                        partners of the <strong>Digital Girls Hub</strong>{" "}
                        initiative, developed by <strong>UNICEF YuWaah</strong>{" "}
                        to accelerate skilling, employment, and social
                        protection outcomes for young women (18–29 years)
                        across India.
                    </p>

                    <p className="welcome__copy">
                        Designed for CSCs, VLEs, and implementation partners,
                        the app enables seamless mobilisation, learner
                        onboarding, progress tracking, placement coordination,
                        and verification through the YouthHub ecosystem.
                    </p>

                    <h2 className="welcome__heading">
                        Key Features of the YuWaah Partner App
                    </h2>

                    <ul className="welcome__features">
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

                    <p className="welcome__copy">
                        By connecting last-mile partners to a unified digital
                        system, the YuWaah Partner App ensures every girl
                        reached is tracked, supported, and connected to
                        opportunities.
                    </p>

                </div>

                <div className="welcome__actions">
                    <a
                        href="/field-agent/login"
                        className="welcome__login"
                    >
                        Login to Partner App
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </section>

            <footer className="workspace__footer">
                <span>YOUTHHUB PARTNER</span>
                <span>Email: support@youthhubpartner.org</span>
            </footer>
        </main>
    );
}
