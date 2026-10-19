
import React, { useCallback, useEffect, useState } from 'react';
import {
    ArrowLeft,
    ClipboardList,
    UserRound,
    Phone,
    FileText,
    MessageSquare,
    Search,
    Upload,
    Save,
    CheckCircle2,
    CircleAlert,
    IndianRupee
} from 'lucide-react';

import './EventActivitiesPage.css';
import ToastNotification from '../../components/ToastNotification';

const API_BASE = '/api/field-agent';
const CREATE_ACTIVITY_URL = `${API_BASE}/event-activities`;

/**
 * AddEventActivityPage
 *
 * Provides a form for creating a new event activity for the authenticated
 * Field Agent. Loads event types, categories, and learner search results
 * from the existing API, then submits the activity using multipart FormData.
 *
 * @param {Object} props - Component properties.
 * @param {Function} [props.onBack] - Called when the user cancels or after
 * successful creation.
 * @param {Function} [props.t] - Translation function.
 * @returns {React.JSX.Element} Event creation form.
 */
export default function AddEventActivityPage({
    onBack,
    t = (key) => key,
}) {
    const [formData, setFormData] = useState({
        learner_id: '',
        event_type_id: '',
        event_master_id: '',
        beneficiary_name: '',
        beneficiary_phone_number: '',
        comment: '',
        workflow_status: 'DRAFT',
        monthly_income: '0.00',
        document_1: null,
        document_2: null,
        document_3: null,
    });

    const [learnerSearch, setLearnerSearch] = useState('');
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [learnerSuggestions, setLearnerSuggestions] = useState([]);
    const [loadingLearners, setLoadingLearners] = useState(false);

    const [eventTypes, setEventTypes] = useState([]);
    const [eventCategories, setEventCategories] = useState([]);
    const [selectedEvent, setSelectedEvent] = useState(null);

    const [loadingTypes, setLoadingTypes] = useState(false);
    const [loadingCategories, setLoadingCategories] = useState(false);
    const [saving, setSaving] = useState(false);

    const [errors, setErrors] = useState({});
    const [apiError, setApiError] = useState('');
    const [toast, setToast] = useState(null);

    /**
     * Closes the current toast notification.
     *
     * @returns {void}
     */
    const closeToast = useCallback(() => {
        setToast(null);
    }, []);

    /**
     * Updates a form field and clears its associated validation error.
     *
     * @param {string} field - Name of the field to update.
     * @param {string|File|null} value - New field value.
     * @returns {void}
     */



     const updateField = (field, value) => {
        setFormData((previous) => ({
            ...previous,
            [field]: value,
        }));

        setErrors((previous) => ({
            ...previous,
            [field]: '',
        }));
    };

    /**
     * Loads the available event types from the Field Agent API.
     *
     * @returns {Promise<void>}
     */
    useEffect(() => {
        const controller = new AbortController();

        async function loadEventTypes() {
            setLoadingTypes(true);

            try {
                const response = await fetch(
                    `${API_BASE}/event-activity-types`,
                    {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Unable to load event types.'
                    );
                }

                setEventTypes(Array.isArray(result.data) ? result.data : []);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setApiError(error.message || 'Unable to load event types.');
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoadingTypes(false);
                }
            }
        }

        loadEventTypes();

        return () => controller.abort();
    }, []);

    /**
     * Loads categories for the selected event type and clears any previous
     * category selection when the event type changes.
     *
     * @param {string} typeId - Selected event type ID.
     * @returns {Promise<void>}
     */
    useEffect(() => {
        const controller = new AbortController();

        async function loadCategories() {
            if (!formData.event_type_id) {
                setEventCategories([]);
                setSelectedEvent(null);
                return;
            }

            setLoadingCategories(true);
            setEventCategories([]);
            setSelectedEvent(null);

            try {
                const response = await fetch(
                    `${API_BASE}/event-activity-types/${formData.event_type_id}/categories`,
                    {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Unable to load event categories.'
                    );
                }

                setEventCategories(
                    Array.isArray(result.data) ? result.data : []
                );
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setApiError(
                        error.message || 'Unable to load event categories.'
                    );
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoadingCategories(false);
                }
            }
        }

        loadCategories();

        return () => controller.abort();
    }, [formData.event_type_id]);

    /**
     * Searches learners after the user enters at least two characters.
     * Requests are debounced and cancelled when the query changes.
     *
     * @returns {void}
     */
    useEffect(() => {
        const query = learnerSearch.trim();

        if (query.length < 2 || !showSuggestions) {
            setLearnerSuggestions([]);
            setLoadingLearners(false);
            return;
        }

        const controller = new AbortController();

        const timer = setTimeout(async () => {
            setLoadingLearners(true);

            try {
                const response = await fetch(
                    `${API_BASE}/learners/search?q=${encodeURIComponent(query)}`,
                    {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Unable to search learners.'
                    );
                }

                setLearnerSuggestions(
                    Array.isArray(result.data) ? result.data : []
                );
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setLearnerSuggestions([]);
                    setApiError(error.message || 'Learner search failed.');
                }
            } finally {
                if (!controller.signal.aborted) {
                    setLoadingLearners(false);
                }
            }
        }, 300);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [learnerSearch, showSuggestions]);

    /**
     * Selects an event type and resets the dependent category and document
     * selections so that stale values are not submitted.
     *
     * @param {string} typeId - Selected event type ID.
     * @returns {void}
     */
    const handleEventTypeChange = (typeId) => {
        setFormData((previous) => ({
            ...previous,
            event_type_id: typeId,
            event_master_id: '',
            document_1: null,
            document_2: null,
            document_3: null,
        }));

        setErrors((previous) => ({
            ...previous,
            event_type_id: '',
            event_master_id: '',
        }));

        setApiError('');
    };

    /**
     * Selects an event category and stores the matching event master ID.
     *
     * @param {string} eventMasterId - Selected event master ID.
     * @returns {void}
     */
    const handleCategoryChange = (eventMasterId) => {
        const selected = eventCategories.find(
            (item) =>
                String(item.event_master_id) === String(eventMasterId)
        );

        setSelectedEvent(selected || null);

        setFormData((previous) => ({
            ...previous,
            event_master_id: eventMasterId,
            document_1: null,
            document_2: null,
            document_3: null,
        }));

        setErrors((previous) => ({
            ...previous,
            event_master_id: '',
        }));
    };


    /**
     * Selects a learner from the search suggestions and fills the beneficiary
     * name, learner ID, and phone number.
     *
     * Supports the existing API's name/phone response fields and common
     * first_name/last_name/phone_number alternatives.
     *
     * @param {Object} learner - Learner returned by the search endpoint.
     * @returns {void}
     */
    const selectLearner = (learner) => {
        const name =
            learner.name ||
            [learner.first_name, learner.last_name].filter(Boolean).join(' ');

        const phone =
            learner.phone ||
            learner.phone_number ||
            learner.normalized_mobile ||
            '';

        setFormData((previous) => ({
            ...previous,
            learner_id: learner.id,
            beneficiary_name: name,
            beneficiary_phone_number: phone,
        }));

        setLearnerSearch(name);
        setShowSuggestions(false);
        setLearnerSuggestions([]);

        setErrors((previous) => ({
            ...previous,
            learner_id: '',
            beneficiary_name: '',
        }));
    };

    /**
     * Validates the form and submits a new event activity to the Laravel API.
     * Uploaded documents are sent as multipart FormData. Workflow and review
     * statuses are assigned by the backend service.
     *
     * @param {React.FormEvent<HTMLFormElement>} event - Form submit event.
     * @returns {Promise<void>}
     */
    const handleSubmit = async (event) => {
        event.preventDefault();

        const newErrors = {};

        if (!formData.learner_id) {
            newErrors.learner_id = 'Please select a learner from the suggestions.';
        }

        if (!formData.event_type_id) {
            newErrors.event_type_id = 'Please select an event type.';
        }

        if (!formData.event_master_id) {
            newErrors.event_master_id = 'Please select an event category.';
        }

        if (formData.monthly_income==0) {
            newErrors.monthly_income = 'Please enter monthly income value.';
        }

        if (!formData.beneficiary_name.trim()) {
            newErrors.beneficiary_name = 'Beneficiary name is required.';
        }

        if (!formData.beneficiary_phone_number.trim()) {
            newErrors.beneficiary_phone_number =
                'Beneficiary phone number is required.';
        }

        setErrors(newErrors);

        if (Object.keys(newErrors).length > 0) {
            return;
        }

        const payload = new FormData();

        payload.append('learner_id', String(formData.learner_id));
        payload.append('event_master_id', String(formData.event_master_id));
        payload.append('event_master_type_id', String(formData.event_type_id));
        payload.append('beneficiary_name', formData.beneficiary_name.trim());
        payload.append('workflow_status', formData.workflow_status);
        payload.append('monthly_income', formData.monthly_income || '0');
        payload.append(
            'beneficiary_phone_number',
            formData.beneficiary_phone_number.trim()
        );
        payload.append('comment', formData.comment || '');

        ['document_1', 'document_2', 'document_3'].forEach((field) => {
            if (formData[field] instanceof File) {
                payload.append(field, formData[field]);
            }
        });

        setSaving(true);
        setApiError('');

        try {
            const response = await fetch(CREATE_ACTIVITY_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: payload,
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                if (response.status === 422 && result.errors) {
                    setErrors((previous) => ({
                        ...previous,
                        ...Object.fromEntries(
                            Object.entries(result.errors).map(([key, messages]) => [
                                key,
                                Array.isArray(messages) ? messages[0] : messages,
                            ])
                        ),
                    }));
                }

                throw new Error(
                    result.message || 'Unable to create event activity.'
                );
            }

            setToast({
                type: 'success',
                title: 'Success!',
                message: result.message || 'Event activity created successfully.',
            });

            if (onBack) {
                onBack(
                    result.message || 'Event activity created successfully.'
                );
            }
        } catch (error) {
            setApiError(error.message || 'Unable to create event activity.');

            setToast({
                type: 'error',
                title: 'Creation Failed',
                message: error.message || 'Please try again.',
            });
        } finally {
            setSaving(false);
        }
    };

    const documentFields = [
        { field: 'document_1', label: selectedEvent?.document_name_1 },
        { field: 'document_2', label: selectedEvent?.document_name_2 },
        { field: 'document_3', label: selectedEvent?.document_name_3 },
    ].filter(
        (item) => typeof item.label === 'string' && item.label.trim()
    );

    return (
        <section className="ea-page ea-edit-page">
            <header className="ea-header">
                <div>
                    <h1>{t('add_event') === 'add_event' ? 'Add Event' : t('add_event')}</h1>
                    <p>Create a new event activity for a learner.</p>
                </div>

                <div className="ea-header-actions">
                    <button
                        type="button"
                        className="ea-back-button"
                        onClick={() => onBack?.()}
                    >
                        <ArrowLeft size={17} />
                        <span>Back to Event List</span>
                    </button>
                </div>
            </header>

            {toast && (
                <ToastNotification
                    type={toast.type}
                    title={toast.title}
                    message={toast.message}
                    onClose={closeToast}
                />
            )}

            {apiError && (
                <div className="ea-error" role="alert">
                    <p>{apiError}</p>
                </div>
            )}

            <form className="ea-edit-form" onSubmit={handleSubmit}>
                <div className="ea-form-card">
                    <div className="ea-form-section-heading">
                        <div className="ea-section-icon">
                            <ClipboardList size={19} />
                        </div>
                        <div>
                            <h2>Event Information</h2>
                            <p>Select the event type and category.</p>
                        </div>
                    </div>

                    <div className="ea-form-grid">
                        <div className="ea-form-field">
                            <label className="ea-form-label">
                                Event Type <span className="ea-required">*</span>
                            </label>

                            <select
                                value={formData.event_type_id}
                                onChange={(event) =>
                                    handleEventTypeChange(event.target.value)
                                }
                                className="ea-form-control"
                                disabled={loadingTypes}
                            >
                                <option value="">
                                    {loadingTypes
                                        ? 'Loading event types...'
                                        : 'Select Event Type'}
                                </option>

                                {eventTypes.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>

                           {/* -----------------------------------------------------------------------
                            | Event Type Validation Error
                            | Displays an accessible inline error when event type is not selected.
                            * ----------------------------------------------------------------------- */}
                            {errors.event_type_id && (
                                <div className="ea-validation-error" role="alert">
                                    <span className="ea-validation-error-icon">
                                        <CircleAlert size={15} />
                                    </span>

                                    <span className="ea-validation-error-message">
                                        {errors.event_type_id}
                                    </span>
                                </div>
                            )}
                        </div>

                        <div className="ea-form-field">
                            <label className="ea-form-label">
                                Event Category <span className="ea-required">*</span>
                            </label>

                            <select
                                value={formData.event_master_id}
                                onChange={(event) =>
                                    handleCategoryChange(event.target.value)
                                }
                                className="ea-form-control"
                                disabled={
                                    !formData.event_type_id ||
                                    loadingCategories
                                }
                            >
                                <option value="">
                                    {loadingCategories
                                        ? 'Loading categories...'
                                        : 'Select Event Category'}
                                </option>

                                {eventCategories.map((item) => (
                                    <option
                                        key={item.event_master_id}
                                        value={item.event_master_id}
                                    >
                                        {item.name}
                                    </option>
                                ))}
                            </select>

                           {/* -----------------------------------------------------------------------
                            | Event Category Validation Error
                            | Displays an accessible inline error when event category is invalid.
                            * ----------------------------------------------------------------------- */}
                            {errors.event_master_id && (
                                <div className="ea-validation-error" role="alert">
                                    <span className="ea-validation-error-icon">
                                        <CircleAlert size={15} />
                                    </span>

                                    <span className="ea-validation-error-message">
                                        {errors.event_master_id}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div className="ea-form-card">
                    <div className="ea-form-section-heading">
                        <div className="ea-section-icon">
                            <UserRound size={19} />
                        </div>
                        <div>
                            <h2>Beneficiary Information</h2>
                            <p>Search and select a learner.</p>
                        </div>
                    </div>

                    <div className="ea-form-grid">
                        <div className="ea-form-field ea-autocomplete-field">
                            <label className="ea-form-label">
                                Beneficiary Name <span className="ea-required">*</span>
                            </label>

                            <div className="ea-input-icon-wrap">
                                <Search size={18} />
                                <input
                                    type="text"
                                    value={learnerSearch}
                                    placeholder="Search learner name or phone..."
                                    className="ea-form-control"
                                    autoComplete="off"
                                    onChange={(event) => {
                                        const value = event.target.value;

                                        setLearnerSearch(value);
                                        setShowSuggestions(true);
                                        setFormData((previous) => ({
                                            ...previous,
                                            learner_id: '',
                                            beneficiary_name: '',
                                            beneficiary_phone_number: '',
                                        }));
                                    }}
                                    onFocus={() => setShowSuggestions(true)}
                                />
                            </div>

                            {showSuggestions && learnerSearch.trim().length >= 2 && (
                                <div className="ea-learner-suggestions">
                                    {loadingLearners ? (
                                        <div className="ea-no-learners">
                                            Searching learners...
                                        </div>
                                    ) : learnerSuggestions.length > 0 ? (
                                        learnerSuggestions.map((learner) => {
                                            const name =
                                                learner.name ||
                                                [
                                                    learner.first_name,
                                                    learner.last_name,
                                                ].filter(Boolean).join(' ');

                                            const phone =
                                                learner.phone ||
                                                learner.phone_number ||
                                                learner.normalized_mobile ||
                                                '';

                                            return (
                                                <button
                                                    type="button"
                                                    key={learner.id}
                                                    className="ea-learner-option"
                                                    onClick={() =>
                                                        selectLearner(learner)
                                                    }
                                                >
                                                    <span className="ea-learner-avatar">
                                                        {(name || '?')
                                                            .charAt(0)
                                                            .toUpperCase()}
                                                    </span>
                                                    <span className="ea-learner-info">
                                                        <strong>{name}</strong>
                                                        <small>
                                                            {phone || 'Mobile not available'}
                                                        </small>
                                                    </span>
                                                </button>
                                            );
                                        })
                                    ) : (
                                        <div className="ea-no-learners">
                                            No learners found
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* -----------------------------------------------------------------------
                            | Learner ID Validation Error
                            | Shows an accessible inline error when learner selection is invalid.
                            * ----------------------------------------------------------------------- */}
                            {errors.learner_id && (
                                <div className="ea-validation-error" role="alert">
                                    <span className="ea-validation-error-icon">
                                        <CircleAlert size={15} />
                                    </span>

                                    <span className="ea-validation-error-message">
                                        {errors.learner_id}
                                    </span>
                                </div>
                            )}

                            {/* -----------------------------------------------------------------------
                            | Beneficiary Name Validation Error
                            | Shows an accessible inline error when the beneficiary name is invalid.
                            * ----------------------------------------------------------------------- */}
                            {errors.beneficiary_name && (
                                <div className="ea-validation-error" role="alert">
                                    <span className="ea-validation-error-icon">
                                        <CircleAlert size={15} />
                                    </span>

                                    <span className="ea-validation-error-message">
                                        {errors.beneficiary_name}
                                    </span>
                                </div>
                            )}
                        </div>

                        <div className="ea-form-field">
                            <label className="ea-form-label">
                                <Phone size={15} /> Beneficiary Phone Number
                            </label>

                            <input
                                type="tel"
                                value={formData.beneficiary_phone_number}
                                className="ea-form-control ea-readonly-control"
                                placeholder="Auto-filled after selecting learner"
                                readOnly
                            />

                           {/* -----------------------------------------------------------------------
                            | Modern Inline Validation Error
                            | Displays a clear, accessible validation message below the input.
                            * ----------------------------------------------------------------------- */}
                            {errors.beneficiary_phone_number && (
                                <div className="ea-validation-error" role="alert">
                                    <span className="ea-validation-error-icon">
                                        <CircleAlert size={15} />
                                    </span>

                                    <span className="ea-validation-error-message">
                                        {errors.beneficiary_phone_number}
                                    </span>
                                </div>
                            )}
                        </div>


                    <div className="ea-form-field">
                        <label className="ea-form-label">
                            <IndianRupee size={15} />
                            Monthly Salary / Income (INR)
                        </label>

                         <input
                            id="monthly_income"
                            name="monthly_income"
                            type="number"
                            min="0"
                            step="0.01"
                            value={formData.monthly_income}
                            onChange={(event) =>
                                updateField('monthly_income', event.target.value)
                            }
                            className="ea-form-control"
                            placeholder="Enter monthly income"
                        />
                        {/* -----------------------------------------------------------------------
                        | Monthly Income Validation Error
                        | Displays an accessible inline validation message for monthly income.
                        * ----------------------------------------------------------------------- */}
                        {errors.monthly_income && (
                            <div className="ea-validation-error" role="alert">
                                <span className="ea-validation-error-icon">
                                    <CircleAlert size={15} />
                                </span>

                                <span className="ea-validation-error-message">
                                    {errors.monthly_income}
                                </span>
                            </div>
                        )}
                    </div>

                    </div>
                </div>

                <div className="ea-form-card">
                    <div className="ea-form-section-heading">
                        <div className="ea-section-icon">
                            <FileText size={19} />
                        </div>
                        <div>
                            <h2>Supporting Documents</h2>
                            <p>Upload documents required for this category.</p>
                        </div>
                    </div>

                    <div className="ea-form-grid">
                        {documentFields.length > 0 ? (
                            documentFields.map(({ field, label }) => (
                                <div className="ea-form-field" key={field}>
                                    <label
                                        className="ea-form-label"
                                        htmlFor={field}
                                    >
                                        {label}
                                    </label>

                                    <div className="ea-input-icon-wrap">
                                        <Upload size={18} />
                                        <input
                                            id={field}
                                            name={field}
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            className="ea-form-control"
                                            onChange={(event) =>
                                                updateField(
                                                    field,
                                                    event.target.files?.[0] ?? null
                                                )
                                            }
                                        />
                                    </div>

                                    {formData[field] && (
                                        <small className="ea-field-hint">
                                            Selected: {formData[field].name}
                                        </small>
                                    )}

                                    {errors[field] && (
                                        <small className="ea-error-text">
                                            {errors[field]}
                                        </small>
                                    )}
                                </div>
                            ))
                        ) : (
                            <p className="ea-field-hint">
                                Select an event category to see its document fields.
                            </p>
                        )}
                    </div>
                </div>

                <div className="ea-form-card">
                    <div className="ea-form-section-heading">
                        <div className="ea-section-icon">
                            <MessageSquare size={19} />
                        </div>
                        <div>
                            <h2>Comments</h2>
                            <p>Add an optional comment for this activity.</p>
                        </div>
                    </div>
                    <div className='ea-form-grid'>
                    <div className="ea-input-icon-wrap">
                        <label className="ea-form-label" htmlFor="event-comment">
                            Comment
                        </label>
                        <input
                            id="event-comment"
                            value={formData.comment}
                            onChange={(event) =>
                                updateField('comment', event.target.value)
                            }
                            className="ea-form-control"
                            maxLength={5000}
                            placeholder="Enter comment or additional information..."
                        />
                        {/* -----------------------------------------------------------------------
                        | Modern Comment Validation Error
                        | Displays an accessible inline error message below the comment field.
                        * ----------------------------------------------------------------------- */}
                        {errors.comment && (
                            <div className="ea-validation-error" role="alert">
                                <span className="ea-validation-error-icon">
                                    <CircleAlert size={15} />
                                </span>

                                <span className="ea-validation-error-message">
                                    {errors.comment}
                                </span>
                            </div>
                        )}
                    </div>
                    {/* -----------------------------------------------------------------------
                | Workflow Status
                | Allows the user to save the activity as Draft or Submitted.
                * ----------------------------------------------------------------------- */}
                <div className="ea-input-icon-wrap">
                    <label htmlFor="workflow_status">
                        Workflow Status <span className="text-danger">*</span>
                    </label>

                    <select
                        id="workflow_status"
                        name="workflow_status"
                        className="ea-form-control"
                        value={formData.workflow_status}
                        onChange={(e) =>
                            setFormData((prev) => ({
                                ...prev,
                                workflow_status: e.target.value,
                            }))
                        }
                        required
                    >
                        <option value="DRAFT">Draft</option>
                        <option value="SUBMITTED">Submitted</option>
                    </select>
                </div>
                </div>
                </div>


                <div className="ea-form-actions">
                    <button
                        type="button"
                        className="ea-cancel-button"
                        onClick={() => onBack?.()}
                        disabled={saving}
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        className="ea-submit-button"
                        disabled={saving}
                    >
                        {saving ? (
                            <span>Creating...</span>
                        ) : (
                            <>
                                <Save size={17} />
                                <span>Create Event</span>
                            </>
                        )}
                    </button>
                </div>
            </form>
        </section>
    );
}
