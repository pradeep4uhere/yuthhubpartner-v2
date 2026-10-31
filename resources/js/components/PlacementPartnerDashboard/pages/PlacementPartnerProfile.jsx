import { useEffect, useState } from "react";
import {
    UserRound,
    LockKeyhole,
    ShieldCheck,
    Bell,
    Mail,
    Phone,
    Building2,
    BadgeCheck,
    Eye,
    EyeOff,
    Save,
    LoaderCircle,
} from "lucide-react";
import "./PlacementPartnerProfile.css";

const API_BASE = "/api/partner-placement";

/**
 * Read the Laravel CSRF token from the page when one is available.
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? "";
}

/**
 * Send a same-origin request to the Placement Partner API.
 */
async function sendProfileRequest(path, options = {}) {
    const response = await fetch(`${API_BASE}${path}`, {
        credentials: "same-origin",
        ...options,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": getCsrfToken(),
            ...options.headers,
        },
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || `Request failed (${response.status})`);
    }

    return data;
}

const tabs = [
    { id: "personal", label: "Personal Information", icon: UserRound },
    { id: "password", label: "Change Password", icon: LockKeyhole },
    { id: "mpin", label: "Set Login MPIN", icon: ShieldCheck },
    { id: "notifications", label: "Email Notifications", icon: Bell },
];

/**
 * Render the Placement Partner profile and account security settings.
 *
 * Pass the authenticated Placement Partner object through the `partner` prop.
 */
export default function PlacementPartnerProfile({ partner = {} }) {
    const [activeTab, setActiveTab] = useState("personal");
    const [feedback, setFeedback] = useState(null);
    const [loading, setLoading] = useState(false);
    const [mpinLoading, setMpinLoading] = useState(true);
    const [mpinIsSet, setMpinIsSet] = useState(false);
    const [mpinEnabled, setMpinEnabled] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showMpin, setShowMpin] = useState(false);

    const [passwordForm, setPasswordForm] = useState({
        current_password: "",
        password: "",
        password_confirmation: "",
    });

    const [mpinForm, setMpinForm] = useState({
        mpin: "",
        mpin_confirmation: "",
    });

    const [notifications, setNotifications] = useState({
        accountUpdates: true,
        securityAlerts: true,
        reports: false,
    });

    /**
     * Load saved MPIN settings when the profile page mounts.
     */
    useEffect(() => {
        let cancelled = false;

        /**
         * Fetch MPIN status without requesting or exposing the stored hash.
         */
        async function loadMpinSettings() {
            try {
                const data = await sendProfileRequest("/mpin/settings");

                if (!cancelled) {
                    setMpinIsSet(Boolean(data.mpin_is_set));
                    setMpinEnabled(Boolean(data.mpin_login_enabled));
                }
            } catch (error) {
                if (!cancelled) {
                    setFeedback({
                        type: "error",
                        message:
                            error.message || "Unable to load MPIN settings.",
                    });
                }
            } finally {
                if (!cancelled) {
                    setMpinLoading(false);
                }
            }
        }

        loadMpinSettings();

        return () => {
            cancelled = true;
        };
    }, []);

    /**
     * Update a single field in the password form.
     */
    function updatePasswordField(event) {
        const { name, value } = event.target;

        setPasswordForm((previous) => ({
            ...previous,
            [name]: value,
        }));
    }

    /**
     * Update a single field in the MPIN form.
     */
    function updateMpinField(event) {
        const { name, value } = event.target;

        setMpinForm((previous) => ({
            ...previous,
            [name]: value.replace(/\D/g, "").slice(0, 6),
        }));
    }

    /**
     * Submit the password change request to the Placement Partner endpoint.
     */
    async function handlePasswordSubmit(event) {
        event.preventDefault();
        setFeedback(null);

        if (passwordForm.password !== passwordForm.password_confirmation) {
            setFeedback({
                type: "error",
                message: "New password confirmation does not match.",
            });
            return;
        }

        setLoading(true);

        try {
            const data = await sendProfileRequest("/change-password", {
                method: "POST",
                body: JSON.stringify({
                    current_password: passwordForm.current_password,
                    password: passwordForm.password,
                    password_confirmation: passwordForm.password_confirmation,
                }),
            });

            setPasswordForm({
                current_password: "",
                password: "",
                password_confirmation: "",
            });

            setFeedback({
                type: "success",
                message:
                    data.message === "password_updated_successfully"
                        ? "Password updated successfully."
                        : data.message || "Password updated successfully.",
            });
        } catch (error) {
            setFeedback({ type: "error", message: error.message });
        } finally {
            setLoading(false);
        }
    }

    /**
     * Save the six-digit MPIN and its login-enabled preference.
     */
    async function handleMpinSubmit(event) {
        event.preventDefault();
        setFeedback(null);

        if (mpinForm.mpin && mpinForm.mpin.length !== 6) {
            setFeedback({
                type: "error",
                message: "MPIN must contain exactly six digits.",
            });
            return;
        }

        if (mpinForm.mpin && mpinForm.mpin !== mpinForm.mpin_confirmation) {
            setFeedback({
                type: "error",
                message: "MPIN confirmation does not match.",
            });
            return;
        }

        setLoading(true);

        try {
            const data = await sendProfileRequest("/mpin/settings", {
                method: "PUT",
                body: JSON.stringify({
                    mpin: mpinForm.mpin || null,
                    mpin_confirmation: mpinForm.mpin
                        ? mpinForm.mpin_confirmation
                        : null,
                    mpin_login_enabled: mpinEnabled,
                }),
            });

            setMpinIsSet(Boolean(data.mpin_is_set));
            setMpinEnabled(Boolean(data.mpin_login_enabled));
            setMpinForm({ mpin: "", mpin_confirmation: "" });

            setFeedback({
                type: "success",
                message: "MPIN settings updated successfully.",
            });
        } catch (error) {
            setFeedback({ type: "error", message: error.message });
        } finally {
            setLoading(false);
        }
    }

    /**
     * Update a notification preference in the current page state.
     */
    function toggleNotification(key) {
        setNotifications((previous) => ({
            ...previous,
            [key]: !previous[key],
        }));
    }

    const placementName = partner.partner_placement_name || "Placement Partner";
    const displayName = partner.name || placementName;
    const email = partner.email || "Not provided";
    const phone = partner.contact_number || partner.phone || "Not provided";
    const code = partner.pp_code || "Not assigned";
    const status = String(partner.status || "Unknown");

    return (
        <div className="pp-profile-page">
            <header className="pp-profile-header">
                <div>
                    <span className="pp-profile-eyebrow">
                        Account Management
                    </span>
                    <h1>My Profile</h1>
                    <p>
                        Manage your Placement Partner details and account
                        security.
                    </p>
                </div>
            </header>

            {feedback && (
                <div
                    className={`pp-profile-feedback pp-profile-feedback--${feedback.type}`}
                    role="status"
                >
                    {feedback.message}
                    <button
                        type="button"
                        aria-label="Dismiss message"
                        onClick={() => setFeedback(null)}
                    >
                        ×
                    </button>
                </div>
            )}

            <div className="pp-profile-layout">
                <aside className="pp-profile-sidebar">
                    <div className="pp-profile-sidebar__heading">
                        Account Settings
                    </div>
                    <nav
                        className="pp-profile-nav"
                        aria-label="Profile settings"
                    >
                        {tabs.map(({ id, label, icon: Icon }) => (
                            <button
                                key={id}
                                type="button"
                                className={`pp-profile-nav__item ${
                                    activeTab === id ? "is-active" : ""
                                }`}
                                onClick={() => {
                                    setActiveTab(id);
                                    setFeedback(null);
                                }}
                            >
                                <Icon size={17} />
                                <span>{label}</span>
                            </button>
                        ))}
                    </nav>
                </aside>

                <main className="pp-profile-content">
                    {activeTab === "personal" && (
                        <>
                            <div className="pp-profile-content__heading">
                                <div className="pp-profile-content__icon">
                                    <UserRound size={22} />
                                </div>
                                <div>
                                    <h2>Personal Information</h2>
                                    <p>
                                        Review the details associated with your
                                        account.
                                    </p>
                                </div>
                            </div>

                            <section className="pp-profile-details">
                                <div className="pp-profile-identity">
                                    <div className="pp-profile-avatar">
                                        <Building2 size={25} />
                                    </div>
                                    <div className="pp-profile-identity__text">
                                        <h3>{displayName}</h3>
                                        <p>{email}</p>
                                    </div>
                                    <span className="pp-profile-account-badge">
                                        <BadgeCheck size={14} />
                                        {status}
                                    </span>
                                </div>

                                <div className="pp-profile-details__grid">
                                    <ProfileDetail
                                        icon={<UserRound size={18} />}
                                        label="Account Name"
                                        value={partner.name}
                                    />
                                    <ProfileDetail
                                        icon={<Building2 size={18} />}
                                        label="Placement Partner Name"
                                        value={placementName}
                                    />
                                    <ProfileDetail
                                        icon={<Mail size={18} />}
                                        label="Email Address"
                                        value={email}
                                    />
                                    <ProfileDetail
                                        icon={<Phone size={18} />}
                                        label="Contact Number"
                                        value={phone}
                                    />
                                    <ProfileDetail
                                        icon={<BadgeCheck size={18} />}
                                        label="Placement Partner Code"
                                        value={code}
                                    />
                                    <ProfileDetail
                                        icon={<ShieldCheck size={18} />}
                                        label="Account Status"
                                        value={status}
                                    />
                                </div>

                                <p className="pp-profile-note">
                                    To update your registered details, contact
                                    your system administrator.
                                </p>
                            </section>
                        </>
                    )}

                    {activeTab === "password" && (
                        <>
                            <ProfileHeading
                                icon={<LockKeyhole size={22} />}
                                title="Change Password"
                                description="Use your current password to set a new secure password."
                            />

                            <form
                                className="pp-profile-form"
                                onSubmit={handlePasswordSubmit}
                            >
                                <ProfileInput
                                    label="Current Password"
                                    name="current_password"
                                    value={passwordForm.current_password}
                                    onChange={updatePasswordField}
                                    type={showPassword ? "text" : "password"}
                                    autoComplete="current-password"
                                    required
                                    trailing={
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setShowPassword(
                                                    (value) => !value,
                                                )
                                            }
                                            aria-label={
                                                showPassword
                                                    ? "Hide passwords"
                                                    : "Show passwords"
                                            }
                                        >
                                            {showPassword ? (
                                                <EyeOff size={17} />
                                            ) : (
                                                <Eye size={17} />
                                            )}
                                        </button>
                                    }
                                />
                                <ProfileInput
                                    label="New Password"
                                    name="password"
                                    value={passwordForm.password}
                                    onChange={updatePasswordField}
                                    type="password"
                                    autoComplete="new-password"
                                    minLength={8}
                                    required
                                />
                                <ProfileInput
                                    label="Confirm New Password"
                                    name="password_confirmation"
                                    value={passwordForm.password_confirmation}
                                    onChange={updatePasswordField}
                                    type="password"
                                    autoComplete="new-password"
                                    minLength={8}
                                    required
                                />
                                <div className="pp-profile-form__actions">
                                    <button
                                        className="pp-profile-primary-button"
                                        disabled={loading}
                                    >
                                        {loading ? (
                                            <LoaderCircle
                                                className="pp-profile-spinner"
                                                size={17}
                                            />
                                        ) : (
                                            <Save size={17} />
                                        )}
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </>
                    )}

                    {activeTab === "mpin" && (
                        <>
                            <ProfileHeading
                                icon={<ShieldCheck size={22} />}
                                title="Login MPIN Settings"
                                description="Configure a six-digit MPIN for an additional login option."
                            />

                            {mpinLoading ? (
                                <p className="pp-profile-note">
                                    Loading MPIN settings…
                                </p>
                            ) : (
                                <form
                                    className="pp-profile-form"
                                    onSubmit={handleMpinSubmit}
                                >
                                    <div className="pp-profile-security-note">
                                        <ShieldCheck size={20} />
                                        <p>
                                            {mpinIsSet
                                                ? "An MPIN has been configured. Enter a new one only if you want to change it."
                                                : "No MPIN is configured yet. Set one before enabling MPIN login."}
                                        </p>
                                    </div>

                                    <ProfileInput
                                        label="New Six-Digit MPIN"
                                        name="mpin"
                                        value={mpinForm.mpin}
                                        onChange={updateMpinField}
                                        type={showMpin ? "text" : "password"}
                                        inputMode="numeric"
                                        pattern="[0-9]{6}"
                                        autoComplete="off"
                                        maxLength={6}
                                        trailing={
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setShowMpin(
                                                        (value) => !value,
                                                    )
                                                }
                                                aria-label={
                                                    showMpin
                                                        ? "Hide MPIN"
                                                        : "Show MPIN"
                                                }
                                            >
                                                {showMpin ? (
                                                    <EyeOff size={17} />
                                                ) : (
                                                    <Eye size={17} />
                                                )}
                                            </button>
                                        }
                                    />
                                    <ProfileInput
                                        label="Confirm MPIN"
                                        name="mpin_confirmation"
                                        value={mpinForm.mpin_confirmation}
                                        onChange={updateMpinField}
                                        type="password"
                                        inputMode="numeric"
                                        pattern="[0-9]{6}"
                                        autoComplete="off"
                                        maxLength={6}
                                    />

                                    <label className="pp-profile-mpin-toggle">
                                        <input
                                            type="checkbox"
                                            checked={mpinEnabled}
                                            onChange={(event) =>
                                                setMpinEnabled(
                                                    event.target.checked,
                                                )
                                            }
                                        />
                                        <span>Enable MPIN login</span>
                                    </label>

                                    <p className="pp-profile-help">
                                        You cannot enable MPIN login until an
                                        MPIN has been configured.
                                    </p>

                                    <div className="pp-profile-form__actions">
                                        <button
                                            className="pp-profile-primary-button"
                                            disabled={
                                                loading ||
                                                (mpinEnabled &&
                                                    !mpinIsSet &&
                                                    !mpinForm.mpin)
                                            }
                                        >
                                            {loading ? (
                                                <LoaderCircle
                                                    className="pp-profile-spinner"
                                                    size={17}
                                                />
                                            ) : (
                                                <Save size={17} />
                                            )}
                                            Save MPIN Settings
                                        </button>
                                    </div>
                                </form>
                            )}
                        </>
                    )}

                    {activeTab === "notifications" && (
                        <>
                            <ProfileHeading
                                icon={<Bell size={22} />}
                                title="Email Notifications"
                                description="Choose which account emails you would like to receive."
                            />

                            <div className="pp-profile-notifications">
                                <NotificationRow
                                    title="Account Updates"
                                    description="Important updates related to your Placement Partner account."
                                    checked={notifications.accountUpdates}
                                    onChange={() =>
                                        toggleNotification("accountUpdates")
                                    }
                                />
                                <NotificationRow
                                    title="Security Alerts"
                                    description="Notifications about password and account security changes."
                                    checked={notifications.securityAlerts}
                                    onChange={() =>
                                        toggleNotification("securityAlerts")
                                    }
                                />
                                <NotificationRow
                                    title="Reports and Summaries"
                                    description="Receive relevant reports and activity summaries by email."
                                    checked={notifications.reports}
                                    onChange={() =>
                                        toggleNotification("reports")
                                    }
                                />
                                <p className="pp-profile-note">
                                    These preferences are currently local to
                                    this page. A backend
                                    notification-preferences endpoint is
                                    required to save them permanently.
                                </p>
                            </div>
                        </>
                    )}
                </main>
            </div>
        </div>
    );
}

/**
 * Render a reusable section heading for a profile settings tab.
 */
function ProfileHeading({ icon, title, description }) {
    return (
        <div className="pp-profile-content__heading">
            <div className="pp-profile-content__icon">{icon}</div>
            <div>
                <h2>{title}</h2>
                <p>{description}</p>
            </div>
        </div>
    );
}

/**
 * Render one read-only account detail with its icon and label.
 */
function ProfileDetail({ icon, label, value }) {
    return (
        <div className="pp-profile-detail">
            <span className="pp-profile-detail__icon">{icon}</span>
            <div>
                <span>{label}</span>
                <strong>{value || "Not provided"}</strong>
            </div>
        </div>
    );
}

/**
 * Render a labeled form field with an optional trailing control.
 */
function ProfileInput({
    label,
    name,
    value,
    onChange,
    type = "text",
    trailing,
    ...inputProps
}) {
    return (
        <div className="pp-profile-field">
            <label htmlFor={`pp-profile-${name}`}>{label}</label>
            <div
                className={`pp-profile-input-wrap ${trailing ? "has-trailing" : ""}`}
            >
                <input
                    id={`pp-profile-${name}`}
                    name={name}
                    type={type}
                    value={value}
                    onChange={onChange}
                    {...inputProps}
                />
                {trailing}
            </div>
        </div>
    );
}

/**
 * Render a notification preference using an accessible toggle control.
 */
function NotificationRow({ title, description, checked, onChange }) {
    return (
        <div className="pp-profile-notification-row">
            <div>
                <h3>{title}</h3>
                <p>{description}</p>
            </div>
            <label className="pp-profile-switch">
                <input
                    type="checkbox"
                    checked={checked}
                    onChange={onChange}
                    aria-label={title}
                />
                <span className="pp-profile-switch__track" />
            </label>
        </div>
    );
}
