import React, { useState, useEffect } from "react";

import {
    UserRound,
    LockKeyhole,
    ShieldCheck,
    BellRing,
    Eye,
    EyeOff,
    Mail,
    Phone,
    Building2,
    MapPin,
    BadgeCheck,
    Save,
} from "lucide-react";

import "./ProfilePage.css";

/* =========================================
   PROFILE SECTIONS
========================================= */

const PROFILE_SECTIONS = [
    {
        id: "personal",
        labelKey: "personal_information",
        icon: UserRound,
    },
    {
        id: "password",
        labelKey: "change_password",
        icon: LockKeyhole,
    },
    {
        id: "mpin",
        labelKey: "set_login_mpin",
        icon: ShieldCheck,
    },
    {
        id: "notifications",
        labelKey: "email_notifications",
        icon: BellRing,
    },
];

/* =========================================
   NOTIFICATION SETTINGS
========================================= */

const NOTIFICATION_ITEMS = [
    {
        id: "learner_updates",
        titleKey: "learner_activity_updates",
        descriptionKey: "learner_activity_updates_description",
    },
    {
        id: "approval_updates",
        titleKey: "approval_notifications",
        descriptionKey: "approval_notifications_description",
    },
    {
        id: "rejection_updates",
        titleKey: "rejection_notifications",
        descriptionKey: "rejection_notifications_description",
    },
    {
        id: "announcements",
        titleKey: "important_announcements",
        descriptionKey: "important_announcements_description",
    },
];

/* =========================================
   API CONFIGURATION
========================================= */

const API_BASE = "/api/partner";

const getCsrfToken = () =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") ?? "";

/* =========================================
   PROFILE COMPONENT
========================================= */

export default function ProfilePage({ partner = {}, t = (key) => key }) {
    const [activeTab, setActiveTab] = useState("personal");

    const [showPassword, setShowPassword] = useState(false);

    const [showMpin, setShowMpin] = useState(false);

    /* =====================================
       PASSWORD STATE
    ===================================== */

    const [passwordForm, setPasswordForm] = useState({
        current_password: "",
        password: "",
        password_confirmation: "",
    });

    const [isPasswordSubmitting, setIsPasswordSubmitting] = useState(false);

    /* =====================================
       NOTIFICATION STATE
    ===================================== */

    const [notifications, setNotifications] = useState({
        learner_updates: true,
        approval_updates: true,
        rejection_updates: true,
        announcements: true,
    });

    /* =====================================
       FEEDBACK STATE
    ===================================== */

    const [message, setMessage] = useState("");

    const [error, setError] = useState("");

    /* =====================================
       MPIN STATE
    ===================================== */

    const [mpinForm, setMpinForm] = useState({
        mpin: "",
        mpin_confirmation: "",
    });

    const [mpinIsSet, setMpinIsSet] = useState(false);

    const [mpinLoginEnabled, setMpinLoginEnabled] = useState(false);

    const [mpinLoading, setMpinLoading] = useState(true);

    const [mpinSubmitting, setMpinSubmitting] = useState(false);

    const [mpinMessage, setMpinMessage] = useState("");

    const [mpinError, setMpinError] = useState("");

    /* =====================================
       ACTIVE SECTION
    ===================================== */

    const activeSection =
        PROFILE_SECTIONS.find((section) => section.id === activeTab) ??
        PROFILE_SECTIONS[0];

    const ActiveIcon = activeSection.icon;

    /* =====================================
       PARTNER PROFILE DATA
    ===================================== */

    const partnerName = partner?.name ?? "";

    const partnerCode = partner?.partnerCode ?? partner?.partner_code ?? "";

    const partnerEmail = partner?.email ?? "";

    const partnerPhone = partner?.phone ?? partner?.contact_number ?? "";

    const partnerStatus = partner?.status ?? "";

    const partnerState = partner?.state ?? "";

    const partnerDistrict = partner?.district ?? "";

    const displayValue = (value) => {
        if (value === null || value === undefined || value === "") {
            return t("not_available");
        }

        if (typeof value === "object") {
            return t("not_available");
        }

        return String(value);
    };

    /* =====================================
       FORM HELPERS
    ===================================== */

    const updatePasswordField = (field, value) => {
        setPasswordForm((previous) => ({
            ...previous,
            [field]: value,
        }));
    };

    const handleSectionChange = (section) => {
        setActiveTab(section);

        setMessage("");
        setError("");

        setMpinMessage("");
        setMpinError("");
    };

    /* =====================================
       CHANGE PASSWORD
    ===================================== */

    const handlePasswordSubmit = async (event) => {
        event.preventDefault();

        if (isPasswordSubmitting) return;

        setMessage("");
        setError("");

        if (
            !passwordForm.current_password ||
            !passwordForm.password ||
            !passwordForm.password_confirmation
        ) {
            setError(t("complete_all_password_fields"));
            return;
        }

        if (passwordForm.password.length < 8) {
            setError(t("password_minimum_length"));
            return;
        }

        if (passwordForm.password !== passwordForm.password_confirmation) {
            setError(t("password_confirmation_mismatch"));
            return;
        }

        setIsPasswordSubmitting(true);

        try {
            const response = await fetch(`${API_BASE}/change-password`, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: JSON.stringify(passwordForm),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if ([401, 419].includes(response.status)) {
                    setError(t("session_expired"));
                    return;
                }

                const firstValidationField = data.errors
                    ? Object.keys(data.errors)[0]
                    : null;

                if (
                    firstValidationField === "password_confirmation" ||
                    data.message === "password_confirmation_mismatch"
                ) {
                    setError(t("password_confirmation_mismatch"));
                } else if (data.message === "current_password_incorrect") {
                    setError(t("current_password_incorrect"));
                } else if (
                    firstValidationField === "password" ||
                    data.message === "password_minimum_length"
                ) {
                    setError(t("password_minimum_length"));
                } else if (data.message === "complete_all_password_fields") {
                    setError(t("complete_all_password_fields"));
                } else {
                    setError(t("password_update_failed"));
                }

                return;
            }

            setPasswordForm({
                current_password: "",
                password: "",
                password_confirmation: "",
            });

            setShowPassword(false);

            setMessage(t(data.message || "password_updated_successfully"));
        } catch (error) {
            console.error("Partner password update failed:", error);

            setError(t("password_update_failed"));
        } finally {
            setIsPasswordSubmitting(false);
        }
    };

    /* =====================================
       LOAD MPIN SETTINGS
    ===================================== */

    useEffect(() => {
        const controller = new AbortController();

        const loadMpinSettings = async () => {
            setMpinLoading(true);
            setMpinError("");

            try {
                const response = await fetch(`${API_BASE}/mpin/settings`, {
                    method: "GET",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    signal: controller.signal,
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(
                        [401, 419].includes(response.status)
                            ? "session_expired"
                            : "mpin_settings_load_failed",
                    );
                }

                if (controller.signal.aborted) return;

                setMpinIsSet(Boolean(data.mpin_is_set));

                setMpinLoginEnabled(Boolean(data.mpin_login_enabled));
            } catch (error) {
                if (error.name !== "AbortError") {
                    setMpinError(
                        t(error.message || "mpin_settings_load_failed"),
                    );
                }
            } finally {
                if (!controller.signal.aborted) {
                    setMpinLoading(false);
                }
            }
        };

        loadMpinSettings();

        return () => controller.abort();
    }, []);

    /* =====================================
       SAVE MPIN SETTINGS
    ===================================== */

    const handleMpinSettingsSubmit = async (event) => {
        event.preventDefault();

        if (mpinSubmitting) return;

        setMpinMessage("");
        setMpinError("");

        const pin = mpinForm.mpin;

        const confirmation = mpinForm.mpin_confirmation;

        const hasNewPin = pin.length > 0;

        if (hasNewPin && !/^\d{6}$/.test(pin)) {
            setMpinError(t("mpin_must_be_six_digits"));
            return;
        }

        if (hasNewPin && pin !== confirmation) {
            setMpinError(t("mpin_confirmation_mismatch"));
            return;
        }

        if (mpinLoginEnabled && !mpinIsSet && !hasNewPin) {
            setMpinError(t("mpin_setup_required"));
            return;
        }

        setMpinSubmitting(true);

        try {
            const payload = {
                mpin_login_enabled: mpinLoginEnabled,
            };

            if (hasNewPin) {
                payload.mpin = pin;
                payload.mpin_confirmation = confirmation;
            }

            const response = await fetch(`${API_BASE}/mpin/settings`, {
                method: "PUT",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                setMpinError(
                    [401, 419].includes(response.status)
                        ? t("session_expired")
                        : t(data.message || "mpin_settings_save_failed"),
                );

                return;
            }

            setMpinIsSet(Boolean(data.mpin_is_set));

            setMpinLoginEnabled(Boolean(data.mpin_login_enabled));

            setMpinForm({
                mpin: "",
                mpin_confirmation: "",
            });

            setMpinMessage(t(data.message || "mpin_settings_updated"));
        } catch (error) {
            console.error("Partner MPIN update failed:", error);

            setMpinError(t("mpin_settings_save_failed"));
        } finally {
            setMpinSubmitting(false);
        }
    };

    /* =====================================
       NOTIFICATIONS
    ===================================== */

    const handleNotificationChange = (id, checked) => {
        setNotifications((previous) => ({
            ...previous,
            [id]: checked,
        }));

        setMessage("");
        setError("");
    };

    const handleNotificationSave = () => {
        setMessage("");

        setError(t("notification_api_not_connected"));
    };

    /* =====================================
       RENDER
    ===================================== */

    return (
        <section className="fa-profile-page">
            {/* HEADER */}

            <header className="fa-profile-page__header">
                <div>
                    <span className="fa-profile-page__eyebrow">
                        {t("account_settings")}
                    </span>

                    <h1>{t("my_profile")}</h1>

                    <p>{t("manage_profile_description")}</p>
                </div>
            </header>

            <div className="fa-profile-layout">
                {/* PROFILE NAVIGATION */}

                <aside className="fa-profile-sidebar">
                    <div className="fa-profile-sidebar__heading">
                        {t("profile_settings")}
                    </div>

                    <nav
                        className="fa-profile-nav"
                        aria-label={t("profile_settings")}
                    >
                        {PROFILE_SECTIONS.map((section) => {
                            const Icon = section.icon;

                            const selected = activeTab === section.id;

                            return (
                                <button
                                    type="button"
                                    key={section.id}
                                    className={`fa-profile-nav__item ${
                                        selected ? "is-active" : ""
                                    }`}
                                    onClick={() =>
                                        handleSectionChange(section.id)
                                    }
                                    aria-current={selected ? "page" : undefined}
                                >
                                    <Icon size={18} aria-hidden="true" />

                                    <span>{t(section.labelKey)}</span>
                                </button>
                            );
                        })}
                    </nav>
                </aside>

                {/* PROFILE CONTENT */}

                <div className="fa-profile-content">
                    <div className="fa-profile-content__heading">
                        <span className="fa-profile-content__icon">
                            <ActiveIcon size={22} aria-hidden="true" />
                        </span>

                        <div>
                            <h2>{t(activeSection.labelKey)}</h2>

                            <p>{t(`${activeTab}_section_description`)}</p>
                        </div>
                    </div>

                    {/* ERROR */}

                    {error && (
                        <div
                            className="fa-profile-feedback fa-profile-feedback--error"
                            role="alert"
                        >
                            {error}
                        </div>
                    )}

                    {/* SUCCESS */}

                    {message && (
                        <div
                            className="fa-profile-feedback fa-profile-feedback--success"
                            role="status"
                        >
                            {message}
                        </div>
                    )}

                    {/* PERSONAL INFORMATION */}

                    {activeTab === "personal" && (
                        <div className="fa-profile-details">
                            <div className="fa-profile-identity">
                                <span className="fa-profile-avatar">
                                    <UserRound size={28} aria-hidden="true" />
                                </span>

                                <div>
                                    <h3>{displayValue(partnerName)}</h3>

                                    <p>{displayValue(partnerCode)}</p>
                                </div>

                                <span className="fa-profile-account-badge">
                                    <BadgeCheck size={15} aria-hidden="true" />

                                    {t("partner")}
                                </span>
                            </div>

                            <div className="fa-profile-details__grid">
                                <ProfileDetail
                                    icon={UserRound}
                                    label={t("partner_name")}
                                    value={partnerName}
                                    emptyLabel={t("not_available")}
                                />

                                <ProfileDetail
                                    icon={BadgeCheck}
                                    label={t("partner_code")}
                                    value={partnerCode}
                                    emptyLabel={t("not_available")}
                                />

                                <ProfileDetail
                                    icon={Phone}
                                    label={t("contact_number")}
                                    value={partnerPhone}
                                    emptyLabel={t("not_available")}
                                />

                                <ProfileDetail
                                    icon={Mail}
                                    label={t("email_address")}
                                    value={partnerEmail}
                                    emptyLabel={t("not_available")}
                                />

                                <ProfileDetail
                                    icon={Building2}
                                    label={t("partner")}
                                    value={
                                        partnerName
                                            ? `${partnerName}${
                                                  partnerCode
                                                      ? ` [${partnerCode}]`
                                                      : ""
                                              }`
                                            : ""
                                    }
                                    emptyLabel={t("not_available")}
                                />

                                <ProfileDetail
                                    icon={BadgeCheck}
                                    label={t("status")}
                                    value={partnerStatus}
                                    emptyLabel={t("not_available")}
                                />

                                <ProfileDetail
                                    icon={MapPin}
                                    label={t("state")}
                                    value={partnerState}
                                    emptyLabel={t("not_available")}
                                />

                                <ProfileDetail
                                    icon={MapPin}
                                    label={t("district")}
                                    value={partnerDistrict}
                                    emptyLabel={t("not_available")}
                                />
                            </div>

                            <p className="fa-profile-note">
                                {t("profile_correction_note")}
                            </p>
                        </div>
                    )}

                    {/* CHANGE PASSWORD */}

                    {activeTab === "password" && (
                        <form
                            className="fa-profile-form"
                            onSubmit={handlePasswordSubmit}
                        >
                            <div className="fa-profile-field">
                                <label htmlFor="profile-current-password">
                                    {t("current_password")}
                                </label>

                                <div className="fa-profile-input-wrap">
                                    <input
                                        id="profile-current-password"
                                        type={
                                            showPassword ? "text" : "password"
                                        }
                                        autoComplete="current-password"
                                        value={passwordForm.current_password}
                                        onChange={(event) =>
                                            updatePasswordField(
                                                "current_password",
                                                event.target.value,
                                            )
                                        }
                                        required
                                    />

                                    <button
                                        type="button"
                                        aria-label={
                                            showPassword
                                                ? t("hide_passwords")
                                                : t("show_passwords")
                                        }
                                        onClick={() =>
                                            setShowPassword((value) => !value)
                                        }
                                    >
                                        {showPassword ? (
                                            <EyeOff size={18} />
                                        ) : (
                                            <Eye size={18} />
                                        )}
                                    </button>
                                </div>
                            </div>

                            <div className="fa-profile-field">
                                <label htmlFor="profile-new-password">
                                    {t("new_password")}
                                </label>

                                <input
                                    id="profile-new-password"
                                    type={showPassword ? "text" : "password"}
                                    autoComplete="new-password"
                                    minLength={8}
                                    value={passwordForm.password}
                                    onChange={(event) =>
                                        updatePasswordField(
                                            "password",
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>

                            <div className="fa-profile-field">
                                <label htmlFor="profile-confirm-password">
                                    {t("confirm_new_password")}
                                </label>

                                <input
                                    id="profile-confirm-password"
                                    type={showPassword ? "text" : "password"}
                                    autoComplete="new-password"
                                    minLength={8}
                                    value={passwordForm.password_confirmation}
                                    onChange={(event) =>
                                        updatePasswordField(
                                            "password_confirmation",
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>

                            <p className="fa-profile-help">
                                {t("password_policy_help")}
                            </p>

                            <div className="fa-profile-form__actions">
                                <button
                                    type="submit"
                                    className="fa-profile-primary-button"
                                    disabled={isPasswordSubmitting}
                                >
                                    <LockKeyhole size={17} />

                                    {isPasswordSubmitting
                                        ? t("updating_password")
                                        : t("update_password")}
                                </button>
                            </div>
                        </form>
                    )}

                    {/* MPIN */}

                    {activeTab === "mpin" && (
                        <form onSubmit={handleMpinSettingsSubmit}>
                            {mpinError && (
                                <div
                                    className="fa-profile-feedback fa-profile-feedback--error"
                                    role="alert"
                                >
                                    {mpinError}
                                </div>
                            )}

                            {mpinMessage && (
                                <div
                                    className="fa-profile-feedback fa-profile-feedback--success"
                                    role="status"
                                >
                                    {mpinMessage}
                                </div>
                            )}

                            {mpinLoading ? (
                                <p>{t("loading_mpin_settings")}</p>
                            ) : (
                                <>
                                    <div className="fa-profile-mpin-field">
                                        <label htmlFor="profile-mpin">
                                            {mpinIsSet
                                                ? t("change_mpin")
                                                : t("set_login_mpin")}
                                        </label>

                                        <input
                                            id="profile-mpin"
                                            type={
                                                showMpin ? "text" : "password"
                                            }
                                            inputMode="numeric"
                                            pattern="[0-9]{6}"
                                            maxLength={6}
                                            autoComplete="new-password"
                                            value={mpinForm.mpin}
                                            placeholder="••••••"
                                            onChange={(event) => {
                                                const value = event.target.value
                                                    .replace(/\D/g, "")
                                                    .slice(0, 6);

                                                setMpinForm((previous) => ({
                                                    ...previous,
                                                    mpin: value,
                                                }));
                                            }}
                                        />

                                        <small>
                                            {mpinIsSet
                                                ? t("mpin_change_hint")
                                                : t("mpin_setup_hint")}
                                        </small>
                                    </div>

                                    <div className="fa-profile-mpin-field">
                                        <label htmlFor="profile-mpin-confirmation">
                                            {t("confirm_mpin")}
                                        </label>

                                        <input
                                            id="profile-mpin-confirmation"
                                            type={
                                                showMpin ? "text" : "password"
                                            }
                                            inputMode="numeric"
                                            pattern="[0-9]{6}"
                                            maxLength={6}
                                            autoComplete="new-password"
                                            value={mpinForm.mpin_confirmation}
                                            placeholder="••••••"
                                            onChange={(event) => {
                                                const value = event.target.value
                                                    .replace(/\D/g, "")
                                                    .slice(0, 6);

                                                setMpinForm((previous) => ({
                                                    ...previous,
                                                    mpin_confirmation: value,
                                                }));
                                            }}
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowMpin((value) => !value)
                                        }
                                    >
                                        {showMpin
                                            ? t("hide_passwords")
                                            : t("show_passwords")}
                                    </button>

                                    <label className="fa-profile-mpin-toggle">
                                        <input
                                            type="checkbox"
                                            checked={mpinLoginEnabled}
                                            onChange={(event) => {
                                                setMpinLoginEnabled(
                                                    event.target.checked,
                                                );

                                                setMpinMessage("");
                                                setMpinError("");
                                            }}
                                        />

                                        <span>{t("enable_mpin_login")}</span>
                                    </label>

                                    <p>
                                        {mpinLoginEnabled
                                            ? t("mpin_login_enabled_hint")
                                            : t("mpin_login_disabled_hint")}
                                    </p>

                                    <button
                                        type="submit"
                                        className="fa-profile-primary-button"
                                        disabled={mpinSubmitting}
                                    >
                                        {mpinSubmitting
                                            ? t("saving_mpin_settings")
                                            : t("save_mpin_settings")}
                                    </button>
                                </>
                            )}
                        </form>
                    )}

                    {/* NOTIFICATIONS */}

                    {activeTab === "notifications" && (
                        <div className="fa-profile-notifications">
                            {NOTIFICATION_ITEMS.map((item) => (
                                <div
                                    className="fa-profile-notification-row"
                                    key={item.id}
                                >
                                    <div>
                                        <h3>{t(item.titleKey)}</h3>

                                        <p>{t(item.descriptionKey)}</p>
                                    </div>

                                    <label className="fa-profile-switch">
                                        <span className="sr-only">
                                            {t(item.titleKey)}
                                        </span>

                                        <input
                                            type="checkbox"
                                            checked={notifications[item.id]}
                                            onChange={(event) =>
                                                handleNotificationChange(
                                                    item.id,
                                                    event.target.checked,
                                                )
                                            }
                                        />

                                        <span
                                            className="fa-profile-switch__track"
                                            aria-hidden="true"
                                        />
                                    </label>
                                </div>
                            ))}

                            <div className="fa-profile-form__actions">
                                <button
                                    type="button"
                                    className="fa-profile-primary-button"
                                    onClick={handleNotificationSave}
                                >
                                    <Save size={17} />

                                    {t("save_preferences")}
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

/* =========================================
   PROFILE DETAIL COMPONENT
========================================= */

function ProfileDetail({ icon: Icon, label, value, emptyLabel }) {
    const isEmpty = value === null || value === undefined || value === "";

    const safeValue =
        isEmpty || typeof value === "object" ? emptyLabel : String(value);

    return (
        <div className="fa-profile-detail">
            <span className="fa-profile-detail__icon">
                <Icon size={18} aria-hidden="true" />
            </span>

            <div>
                <span>{label}</span>

                <strong>{safeValue}</strong>
            </div>
        </div>
    );
}
