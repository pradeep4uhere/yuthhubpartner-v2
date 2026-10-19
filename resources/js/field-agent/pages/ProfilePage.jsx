
import React, { useState, useEffect  } from 'react';
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
} from 'lucide-react';

import './ProfilePage.css';

/* --------------------------------------------------------------------------
 | Profile Navigation
 | Stores translation keys instead of hardcoded labels so Laravel can
 | translate section names according to the active application language.
 * -------------------------------------------------------------------------- */
const PROFILE_SECTIONS = [
    {
        id: 'personal',
        labelKey: 'personal_information',
        icon: UserRound,
    },
    {
        id: 'password',
        labelKey: 'change_password',
        icon: LockKeyhole,
    },
    {
        id: 'mpin',
        labelKey: 'set_login_mpin',
        icon: ShieldCheck,
    },
    {
        id: 'notifications',
        labelKey: 'email_notifications',
        icon: BellRing,
    },
];

/* --------------------------------------------------------------------------
 | Notification Settings
 | Keeps notification labels and descriptions in Laravel language files.
 * -------------------------------------------------------------------------- */
const NOTIFICATION_ITEMS = [
    {
        id: 'learner_updates',
        titleKey: 'learner_activity_updates',
        descriptionKey: 'learner_activity_updates_description',
    },
    {
        id: 'approval_updates',
        titleKey: 'approval_notifications',
        descriptionKey: 'approval_notifications_description',
    },
    {
        id: 'rejection_updates',
        titleKey: 'rejection_notifications',
        descriptionKey: 'rejection_notifications_description',
    },
    {
        id: 'announcements',
        titleKey: 'important_announcements',
        descriptionKey: 'important_announcements_description',
    },
];

/**
 * Render the Field Agent profile and account settings.
 *
 * Translation strings are provided by the parent dashboard through the
 * t prop, following the same pattern used by LearnersPage.
 *
 * Password and MPIN forms perform client-side validation. Backend updates
 * must be connected separately before these settings can be saved.
 *
 * @param {Object} props Component properties.
 * @param {Object} [props.agent={}] Authenticated field-agent details.
 * @param {Function} [props.t] Laravel translation lookup function.
 * @returns {JSX.Element} The translated profile page.
 */
export default function ProfilePage({ agent = {}, t = (key) => key }) {
    const [activeTab, setActiveTab] = useState('personal');
    const [showPassword, setShowPassword] = useState(false);
    const [showMpin, setShowMpin] = useState(false);

    const [passwordForm, setPasswordForm] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const [notifications, setNotifications] = useState({
        learner_updates: true,
        approval_updates: true,
        rejection_updates: true,
        announcements: true,
    });

    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [isPasswordSubmitting, setIsPasswordSubmitting] = useState(false);

     /*
    * Field Agent MPIN settings state.
    * Keeps MPIN feedback separate from the password form feedback.
    */
    const [mpinForm, setMpinForm] = useState({
        mpin: '',
        mpin_confirmation: '',
    });

    const [mpinIsSet, setMpinIsSet] = useState(false);
    const [mpinLoginEnabled, setMpinLoginEnabled] = useState(false);
    const [mpinLoading, setMpinLoading] = useState(true);
    const [mpinSubmitting, setMpinSubmitting] = useState(false);
    const [mpinMessage, setMpinMessage] = useState('');
    const [mpinError, setMpinError] = useState('');

    /*
    * Tracks the progress of saving MPIN settings.
    */
    const [loading, setLoading] = useState(false);





    const activeSection =
        PROFILE_SECTIONS.find((section) => section.id === activeTab) ??
        PROFILE_SECTIONS[0];

    const ActiveIcon = activeSection.icon;

    /**
     * Update one password form field.
     *
     * @param {string} field Password field name.
     * @param {string} value New field value.
     * @returns {void}
     */
    const updatePasswordField = (field, value) => {
        setPasswordForm((previous) => ({
            ...previous,
            [field]: value,
        }));
    };

    /**
     * Update one MPIN form field.
     *
     * @param {string} field MPIN field name.
     * @param {string} value New field value.
     * @returns {void}
     */
    const updateMpinField = (field, value) => {
        setMpinForm((previous) => ({
            ...previous,
            [field]: value,
        }));
    };

    /**
     * Display a profile value or a translated fallback when it is missing.
     *
     * @param {string|number|null|undefined} value Profile value.
     * @returns {string} Profile value or translated fallback.
     */
    const displayValue = (value) => {
        return value === null || value === undefined || value === ''
            ? t('not_available')
            : String(value);
    };

    /**
     * Switch the active profile section and clear previous feedback messages.
     *
     * @param {string} section Profile section identifier.
     * @returns {void}
     */
    const handleSectionChange = (section) => {
        setActiveTab(section);
        setMessage('');
        setError('');
    };

    /**
     * Validate the password form and submit the password change to Laravel.
     *
     * Sends the current password and the new password to the authenticated
     * Field Agent endpoint, displays translated feedback, and clears the
     * password fields after a successful update.
     *
     * @param {React.FormEvent<HTMLFormElement>} event Form submission event.
     * @returns {Promise<void>}
     */
    const handlePasswordSubmit = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        if (isPasswordSubmitting) {
            return;
        }

        if (
            !passwordForm.current_password ||
            !passwordForm.password ||
            !passwordForm.password_confirmation
        ) {
            setError(t('complete_all_password_fields'));
            return;
        }

        if (passwordForm.password.length < 8) {
            setError(t('password_minimum_length'));
            return;
        }

        if (passwordForm.password !== passwordForm.password_confirmation) {
            setError(t('password_confirmation_mismatch'));
            return;
        }

        setIsPasswordSubmitting(true);

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';

            const response = await fetch('/api/field-agent/change-password', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(passwordForm),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 401) {
                    setError(t('session_expired'));
                    return;
                }

                const firstValidationField = data.errors
                    ? Object.keys(data.errors)[0]
                    : null;

                if (
                    firstValidationField === 'password_confirmation' ||
                    data.message === 'password_confirmation_mismatch'
                ) {
                    setError(t('password_confirmation_mismatch'));
                } else if (
                    data.message === 'current_password_incorrect'
                ) {
                    setError(t('current_password_incorrect'));
                } else if (
                    firstValidationField === 'password' ||
                    data.message === 'password_minimum_length'
                ) {
                    setError(t('password_minimum_length'));
                } else if (
                    data.message === 'complete_all_password_fields'
                ) {
                    setError(t('complete_all_password_fields'));
                } else {
                    setError(t('password_update_failed'));
                }

                return;
            }

            setPasswordForm({
                current_password: '',
                password: '',
                password_confirmation: '',
            });

            setShowPassword(false);
            setMessage(t(data.message || 'password_updated_successfully'));
        } catch {
            setError(t('password_update_failed'));
        } finally {
            setIsPasswordSubmitting(false);
        }
    };


    /**
     * Save the Field Agent's MPIN using the authenticated Laravel API.
     *
     * Validates the six-digit MPIN and confirmation, sends the MPIN to
     * Laravel, and displays success or server-side validation feedback.
     *
     * @param {React.FormEvent<HTMLFormElement>} event Form submission event.
     * @returns {Promise<void>}
     */
    const handleMpinSubmit = async (event) => {
        event.preventDefault();
        setMessage('');
        setError('');

        const mpin = mpinForm.mpin;
        const mpinConfirmation = mpinForm.mpin_confirmation;

        if (!/^\d{6}$/.test(mpin)) {
            setError(t('mpin_must_be_six_digits'));
            return;
        }

        if (mpin !== mpinConfirmation) {
            setError(t('mpin_confirmation_mismatch'));
            return;
        }

        setLoading(true);

        try {
            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '';

            const response = await fetch(
                '/api/field-agent/mpin/settings',
                {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        mpin: mpin,
                        mpin_confirmation: mpinConfirmation,
                        mpin_login_enabled: true,
                    }),
                }
            );

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                setError(
                    data.message || t('mpin_settings_save_failed')
                );
                return;
            }

            setMessage(t('mpin_settings_updated'));

            setMpinForm((previous) => ({
                ...previous,
                mpin: '',
                mpin_confirmation: '',
            }));
        } catch (error) {
            console.error('MPIN API request failed:', error);
            setError(t('mpin_settings_save_failed'));
        } finally {
            setLoading(false);
        }
    };

    /**
     * Inform the user that notification preferences are not yet persisted.
     *
     * @returns {void}
     */
    const handleNotificationSave = () => {
        setMessage('');
        setError(t('notification_api_not_connected'));
    };

    /**
     * Toggle a notification preference and clear previous feedback.
     *
     * @param {string} id Notification preference identifier.
     * @param {boolean} checked Whether the preference is enabled.
     * @returns {void}
     */
    const handleNotificationChange = (id, checked) => {
        setNotifications((previous) => ({
            ...previous,
            [id]: checked,
        }));

        setMessage('');
        setError('');
    };

    /*
    * Load the authenticated Field Agent's MPIN settings from Laravel.
    * The API returns only setup/enabled status, never the MPIN hash.
    */
    useEffect(() => {
        let cancelled = false;

        const loadMpinSettings = async () => {
            setMpinLoading(true);

            try {
                const response = await fetch(
                    '/api/field-agent/mpin/settings',
                    {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                );

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(
                        response.status === 401
                            ? 'session_expired'
                            : 'mpin_settings_load_failed'
                    );
                }

                if (cancelled) {
                    return;
                }

                setMpinIsSet(Boolean(data.mpin_is_set));
                setMpinLoginEnabled(Boolean(data.mpin_login_enabled));
            } catch (error) {
                if (!cancelled) {
                    setMpinError(t(error.message || 'mpin_settings_load_failed'));
                }
            } finally {
                if (!cancelled) {
                    setMpinLoading(false);
                }
            }
        };

        loadMpinSettings();

        return () => {
            cancelled = true;
        };
    }, []);



    /**
     * Save the Field Agent's MPIN and login preference.
     *
     * Validates the six-digit MPIN when supplied, submits the settings
     * to Laravel, and refreshes the local state after a successful save.
     *
     * @param {React.FormEvent<HTMLFormElement>} event Form submission event.
     * @returns {Promise<void>}
     */
    const handleMpinSettingsSubmit = async (event) => {
        event.preventDefault();
        setMpinMessage('');
        setMpinError('');

        if (mpinSubmitting) {
            return;
        }

        const pin = mpinForm.mpin;
        const confirmation = mpinForm.mpin_confirmation;
        const hasNewPin = pin.length > 0;

        if (hasNewPin && !/^\d{6}$/.test(pin)) {
            setMpinError(t('mpin_must_be_six_digits'));
            return;
        }

        if (hasNewPin && pin !== confirmation) {
            setMpinError(t('mpin_confirmation_mismatch'));
            return;
        }

        if (mpinLoginEnabled && !mpinIsSet && !hasNewPin) {
            setMpinError(t('mpin_setup_required'));
            return;
        }

        setMpinSubmitting(true);

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';

            const payload = {
                mpin_login_enabled: mpinLoginEnabled,
            };

            if (hasNewPin) {
                payload.mpin = pin;
                payload.mpin_confirmation = confirmation;
            }

            const response = await fetch(
                '/api/field-agent/mpin/settings',
                {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                }
            );

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 401) {
                    setMpinError(t('session_expired'));
                } else {
                    setMpinError(
                        t(data.message || 'mpin_settings_save_failed')
                    );
                }

                return;
            }

            setMpinIsSet(Boolean(data.mpin_is_set));
            setMpinLoginEnabled(Boolean(data.mpin_login_enabled));

            setMpinForm({
                mpin: '',
                mpin_confirmation: '',
            });

            setMpinMessage(t(data.message || 'mpin_settings_updated'));
        } catch {
            setMpinError(t('mpin_settings_save_failed'));
        } finally {
            setMpinSubmitting(false);
        }
    };


    return (
        <section className="fa-profile-page">
            {/* ----------------------------------------------------------------
             | Profile Page Header
             | All user-facing labels use the parent-provided translation
             | function, matching LearnersPage.
             * ---------------------------------------------------------------- */}
            <header className="fa-profile-page__header">
                <div>
                    <span className="fa-profile-page__eyebrow">
                        {t('account_settings')}
                    </span>

                    <h1>{t('my_profile')}</h1>

                    <p>{t('manage_profile_description')}</p>
                </div>
            </header>

            <div className="fa-profile-layout">
                {/* ------------------------------------------------------------
                 | Profile Navigation Sidebar
                 * ------------------------------------------------------------ */}
                <aside className="fa-profile-sidebar">
                    <div className="fa-profile-sidebar__heading">
                        {t('profile_settings')}
                    </div>

                    <nav
                        className="fa-profile-nav"
                        aria-label={t('profile_settings')}
                    >
                        {PROFILE_SECTIONS.map((section) => {
                            const Icon = section.icon;
                            const selected = activeTab === section.id;

                            return (
                                <button
                                    type="button"
                                    key={section.id}
                                    className={`fa-profile-nav__item ${
                                        selected ? 'is-active' : ''
                                    }`}
                                    onClick={() =>
                                        handleSectionChange(section.id)
                                    }
                                    aria-current={
                                        selected ? 'page' : undefined
                                    }
                                >
                                    <Icon size={18} aria-hidden="true" />
                                    <span>{t(section.labelKey)}</span>
                                </button>
                            );
                        })}
                    </nav>
                </aside>

                {/* ------------------------------------------------------------
                 | Active Profile Section
                 * ------------------------------------------------------------ */}
                <div className="fa-profile-content">
                    <div className="fa-profile-content__heading">
                        <span className="fa-profile-content__icon">
                            <ActiveIcon size={22} aria-hidden="true" />
                        </span>

                        <div>
                            <h2>{t(activeSection.labelKey)}</h2>

                            <p>
                                {t(`${activeTab}_section_description`)}
                            </p>
                        </div>
                    </div>

                    {/* Translated validation and integration feedback */}
                    {error && (
                        <div
                            className="fa-profile-feedback fa-profile-feedback--error"
                            role="alert"
                        >
                            {error}
                        </div>
                    )}

                    {message && (
                        <div
                            className="fa-profile-feedback fa-profile-feedback--success"
                            role="status"
                        >
                            {message}
                        </div>
                    )}

                    {/* --------------------------------------------------------
                     | Personal Information
                     * -------------------------------------------------------- */}
                    {activeTab === 'personal' && (
                        <div className="fa-profile-details">
                            <div className="fa-profile-identity">
                                <span className="fa-profile-avatar">
                                    <UserRound
                                        size={28}
                                        aria-hidden="true"
                                    />
                                </span>

                                <div>
                                    <h3>{displayValue(agent.name)}</h3>
                                    <p>{displayValue(agent.sakhiId)}</p>
                                </div>

                                <span className="fa-profile-account-badge">
                                    <BadgeCheck
                                        size={15}
                                        aria-hidden="true"
                                    />
                                    {t('field_agent')}
                                </span>
                            </div>

                            <div className="fa-profile-details__grid">
                                <ProfileDetail
                                    icon={UserRound}
                                    label={t('agent_name')}
                                    value={agent.name}
                                    emptyLabel={t('not_available')}
                                />

                                <ProfileDetail
                                    icon={BadgeCheck}
                                    label={t('sakhi_id')}
                                    value={agent.sakhiId}
                                    emptyLabel={t('not_available')}
                                />

                                <ProfileDetail
                                    icon={Phone}
                                    label={t('contact_number')}
                                    value={agent.contactNumber}
                                    emptyLabel={t('not_available')}
                                />

                                <ProfileDetail
                                    icon={Mail}
                                    label={t('email_address')}
                                    value={agent.email}
                                    emptyLabel={t('not_available')}
                                />

                                <ProfileDetail
                                    icon={Building2}
                                    label={t('partner')}
                                    value={
                                        agent.partnerName
                                            ? `${agent.partnerName}${
                                                  agent.partnerCode
                                                      ? ` [${agent.partnerCode}]`
                                                      : ''
                                              }`
                                            : ''
                                    }
                                    emptyLabel={t('not_available')}
                                />

                                <ProfileDetail
                                    icon={MapPin}
                                    label={t('partner_division')}
                                    value={
                                        agent.centerName
                                            ? `${agent.centerName}${
                                                  agent.centerCode
                                                      ? ` [${agent.centerCode}]`
                                                      : ''
                                              }`
                                            : ''
                                    }
                                    emptyLabel={t('not_available')}
                                />

                                <ProfileDetail
                                    icon={BadgeCheck}
                                    label={t('csc_id')}
                                    value={agent.cscId}
                                    emptyLabel={t('not_available')}
                                />

                                <ProfileDetail
                                    icon={MapPin}
                                    label={t('state')}
                                    value={agent.state}
                                    emptyLabel={t('not_available')}
                                />

                                <ProfileDetail
                                    icon={MapPin}
                                    label={t('district')}
                                    value={agent.district}
                                    emptyLabel={t('not_available')}
                                />
                            </div>

                            <p className="fa-profile-note">
                                {t('profile_correction_note')}
                            </p>
                        </div>
                    )}

                    {/* --------------------------------------------------------
                     | Change Password
                     * -------------------------------------------------------- */}
                    {activeTab === 'password' && (
                        <form
                            className="fa-profile-form"
                            onSubmit={handlePasswordSubmit}
                        >
                            <div className="fa-profile-field">
                                <label htmlFor="profile-current-password">
                                    {t('current_password')}
                                </label>

                                <div className="fa-profile-input-wrap">
                                    <input
                                        id="profile-current-password"
                                        type={
                                            showPassword ? 'text' : 'password'
                                        }
                                        autoComplete="current-password"
                                        value={
                                            passwordForm.current_password
                                        }
                                        onChange={(event) =>
                                            updatePasswordField(
                                                'current_password',
                                                event.target.value
                                            )
                                        }
                                        required
                                    />

                                    <button
                                        type="button"
                                        aria-label={
                                            showPassword
                                                ? t('hide_passwords')
                                                : t('show_passwords')
                                        }
                                        onClick={() =>
                                            setShowPassword((value) => !value)
                                        }
                                    >
                                        {showPassword ? (
                                            <EyeOff
                                                size={18}
                                                aria-hidden="true"
                                            />
                                        ) : (
                                            <Eye
                                                size={18}
                                                aria-hidden="true"
                                            />
                                        )}
                                    </button>
                                </div>
                            </div>

                            <div className="fa-profile-field">
                                <label htmlFor="profile-new-password">
                                    {t('new_password')}
                                </label>

                                <input
                                    id="profile-new-password"
                                    type={
                                        showPassword ? 'text' : 'password'
                                    }
                                    autoComplete="new-password"
                                    minLength={8}
                                    value={passwordForm.password}
                                    onChange={(event) =>
                                        updatePasswordField(
                                            'password',
                                            event.target.value
                                        )
                                    }
                                    required
                                />
                            </div>

                            <div className="fa-profile-field">
                                <label htmlFor="profile-confirm-password">
                                    {t('confirm_new_password')}
                                </label>

                                <input
                                    id="profile-confirm-password"
                                    type={
                                        showPassword ? 'text' : 'password'
                                    }
                                    autoComplete="new-password"
                                    minLength={8}
                                    value={
                                        passwordForm.password_confirmation
                                    }
                                    onChange={(event) =>
                                        updatePasswordField(
                                            'password_confirmation',
                                            event.target.value
                                        )
                                    }
                                    required
                                />
                            </div>

                            <p className="fa-profile-help">
                                {t('password_policy_help')}
                            </p>

                            <div className="fa-profile-form__actions">
                                <button
                                    type="submit"
                                    className="fa-profile-primary-button"
                                    disabled={isPasswordSubmitting}
                                >
                                    <LockKeyhole size={17} aria-hidden="true" />
                                    {isPasswordSubmitting
                                        ? t('updating_password')
                                        : t('update_password')}
                                </button>
                            </div>
                        </form>
                    )}

                    {/* --------------------------------------------------------
                     | Set Login MPIN
                     * -------------------------------------------------------- */}
                    {activeTab === 'mpin' && (

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
                                <p>{t('loading_mpin_settings')}</p>
                            ) : (
                                <>
                                    <div className="fa-profile-mpin-field">
                                        <label htmlFor="profile-mpin">
                                            {mpinIsSet
                                                ? t('change_mpin')
                                                : t('set_login_mpin')}
                                        </label>

                                        <input
                                            id="profile-mpin"
                                            type="password"
                                            inputMode="numeric"
                                            pattern="[0-9]{6}"
                                            maxLength={6}
                                            autoComplete="new-password"
                                            value={mpinForm.mpin}
                                            placeholder="••••••"
                                            onChange={(event) => {
                                                const value = event.target.value
                                                    .replace(/\D/g, '')
                                                    .slice(0, 6);

                                                setMpinForm((previous) => ({
                                                    ...previous,
                                                    mpin: value,
                                                }));
                                            }}
                                            aria-describedby="profile-mpin-help"
                                        />

                                        <small id="profile-mpin-help">
                                            {mpinIsSet
                                                ? t('mpin_change_hint')
                                                : t('mpin_setup_hint')}
                                        </small>
                                    </div>

                                    <div className="fa-profile-mpin-field">
                                        <label htmlFor="profile-mpin-confirmation">
                                            {t('confirm_mpin')}
                                        </label>

                                        <input
                                            id="profile-mpin-confirmation"
                                            type="password"
                                            inputMode="numeric"
                                            pattern="[0-9]{6}"
                                            maxLength={6}
                                            autoComplete="new-password"
                                            value={mpinForm.mpin_confirmation}
                                            placeholder="••••••"
                                            onChange={(event) => {
                                                const value = event.target.value
                                                    .replace(/\D/g, '')
                                                    .slice(0, 6);

                                                setMpinForm((previous) => ({
                                                    ...previous,
                                                    mpin_confirmation: value,
                                                }));
                                            }}
                                        />
                                    </div>

                                    <label className="fa-profile-mpin-toggle">
                                        <input
                                            type="checkbox"
                                            checked={mpinLoginEnabled}
                                            onChange={(event) => {
                                                setMpinLoginEnabled(event.target.checked);
                                                setMpinMessage('');
                                                setMpinError('');
                                            }}
                                        />

                                        <span>{t('enable_mpin_login')}</span>
                                    </label>

                                    <p>
                                        {mpinLoginEnabled
                                            ? t('mpin_login_enabled_hint')
                                            : t('mpin_login_disabled_hint')}
                                    </p>

                                   <button
                                        type="submit"
                                        className="fa-profile-primary-button"
                                        disabled={loading}
                                    >
                                        {loading
                                            ? t('saving_mpin_settings')
                                            : t('save_mpin_settings')}
                                    </button>
                                </>
                            )}
                        </form>

                    )}

                    {/* --------------------------------------------------------
                     | Email Notifications
                     * -------------------------------------------------------- */}
                    {activeTab === 'notifications' && (
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
                                                    event.target.checked
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
                                    <Save size={17} aria-hidden="true" />
                                    {t('save_preferences')}
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

/**
 * Render one read-only profile detail.
 *
 * @param {Object} props Component properties.
 * @param {React.ComponentType} props.icon Icon component.
 * @param {string} props.label Translated detail label.
 * @param {string|number|null|undefined} props.value Detail value.
 * @param {string} props.emptyLabel Translated fallback for missing values.
 * @returns {JSX.Element} A profile detail row.
 */
function ProfileDetail({ icon: Icon, label, value, emptyLabel }) {
    const isEmpty =
        value === null ||
        value === undefined ||
        value === '';

    return (
        <div className="fa-profile-detail">
            <span className="fa-profile-detail__icon">
                <Icon size={18} aria-hidden="true" />
            </span>

            <div>
                <span>{label}</span>
                <strong>
                    {isEmpty ? emptyLabel : String(value)}
                </strong>
            </div>
        </div>
    );
}
