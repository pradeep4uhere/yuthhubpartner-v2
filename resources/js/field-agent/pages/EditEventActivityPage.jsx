import React, { useEffect, useState,useCallback  } from 'react';
import {
    ArrowLeft,
    ClipboardList,
    UserRound,
    Phone,
    IndianRupee,
    FileText,
    MessageSquare,
    Search,
    Upload,
    Save,
    CheckCircle2,
    FilePenLine,
    X,
} from 'lucide-react';

import './EventActivitiesPage.css';
import ToastNotification from "../../components/ToastNotification";

export default function EditEventActivityPage({
    activityId,
    onBack,
    t = (key) => key,
}) {
    const [formData, setFormData] = useState({
        learner_id:'',
        event_type_id: '',
        event_category_id: '',
        event_master_id: '',
        beneficiary_name: '',
        beneficiary_phone_number: '',
        monthly_income: '',
        document_1: null,
        document_2: null,
        document_3: null,
        comment: '',
        workflow_status: 'ReSubmitted',
    });

    // UI-only sample suggestions. API se baad mein connect karenge.
    const [successMessage, setSuccessMessage] = useState('');
    const [learnerSearch, setLearnerSearch] = useState('');
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [learnerSuggestions, setLearnerSuggestions] = useState([]);
    const [loadingLearners, setLoadingLearners] = useState(false);

    const [errors, setErrors] = useState({});
    const [eventTypes, setEventTypes] = useState([]);
    const [eventCategories, setEventCategories] = useState([]);
    const [selectedEvent, setSelectedEvent] = useState(null);

    const [activityLogs, setActivityLogs] = useState([]);
    const [loadingLogs, setLoadingLogs] = useState(false);

    const [loadingTypes, setLoadingTypes] = useState(false);
    const [loadingCategories, setLoadingCategories] = useState(false);
    const [apiError, setApiError] = useState('');
    const [saving, setSaving] = useState(false);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState('');
    const [existingDocuments, setExistingDocuments] = useState({
        document_1: '',
        document_2: '',
        document_3: '',
    });

    const [toast, setToast] = useState(null);
    const [currentWorkflowStatus, setCurrentWorkflowStatus] = useState('');

    const closeToast = useCallback(() => {
        setToast(null);
    }, []);

    const API_BASE = '/api/field-agent';

    const filteredLearners = [];

    const documentFields = [
        {
            field: 'document_1',
            label: selectedEvent?.document_name_1,
        },
        {
            field: 'document_2',
            label: selectedEvent?.document_name_2,
        },
        {
            field: 'document_3',
            label: selectedEvent?.document_name_3,
        },
    ].filter(
        (item) =>
            typeof item.label === 'string' &&
            item.label.trim().length > 0
    );



    useEffect(() => {
        if (!successMessage) return;

        const timer = setTimeout(() => {
            setSuccessMessage('');
        }, 4000);

        return () => clearTimeout(timer);
    }, [successMessage]);


    useEffect(() => {
    let cancelled = false;

    const loadActivity = async () => {
        setLoading(true);
        setLoadError('');

        try {
            const response = await fetch(
                `/api/field-agent/event-activities/${activityId}`,
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'include',
                }
            );

            const result = await response.json();

            if (!response.ok || result.success === false) {
                throw new Error(
                    result.message || 'Unable to load event activity.'
                );
            }
            const activity =
                result.data?.activity ??
                result.data?.data ??
                result.data;
            console.log('Event Transaction Details:', result.data);
            console.log('Workflow Status:', activity?.workflow_status);
            // Support common Laravel response shapes.


            if (!activity || activity.id == null) {
                throw new Error('Event activity data was not found.');
            }

            if (cancelled) return;

            const logs =
                activity.logs ??
                activity.activity_logs ??
                result.data?.logs ??
                result.logs ??
                [];

            setActivityLogs(
                Array.isArray(logs)
                    ? [...logs].sort(
                        (a, b) =>
                            new Date(b.created_at || 0).getTime() -
                            new Date(a.created_at || 0).getTime()
                    )
                    : []
            );

            setFormData({
                learner_id: activity.learner_id ?? '',
                event_master_id: activity.event_master_id ?? '',
                event_type_id:
                    activity.event_master_type_id ??
                    activity.event_type_id ??
                    '',
                event_category_id: activity.event_category_id ?? '',
                beneficiary_name: activity.beneficiary_name ?? '',
                beneficiary_phone_number:
                    activity.beneficiary_phone_number ?? '',
                monthly_income:
                    activity.monthly_income ??
                    activity.event_value ??
                    '',
                document_1: null,
                document_2: null,
                document_3: null,
                comment:
                    activity.last_comment ??
                    activity.comment ??
                    '',
                workflow_status: activity.workflow_status ?? '',
            });

            setLearnerSearch(activity.beneficiary_name ?? '');

            setExistingDocuments({
                document_1: activity.uploaded_doc_1 ?? '',
                document_2: activity.uploaded_doc_2 ?? '',
                document_3: activity.uploaded_doc_3 ?? '',
            });

            setCurrentWorkflowStatus(
                String(activity?.workflow_status ?? '').trim().toLowerCase()
            );

        } catch (error) {
            if (!cancelled) {
                setLoadError(
                    error.message || 'Unable to load event activity.'
                );
            }
        } finally {
            if (!cancelled) {
                setLoading(false);
            }
        }
    };

    if (activityId != null) {
        loadActivity();
    } else {
        setLoading(false);
        setLoadError('Activity ID is missing.');
    }

    return () => {
        cancelled = true;
    };
}, [activityId]);


    useEffect(() => {
        let cancelled = false;

        async function loadEventTypes() {
            setLoadingTypes(true);
            setApiError('');

            try {
                const response = await fetch(
                    `${API_BASE}/event-activity-types`,
                    {
                        credentials: 'include',
                        headers: {
                            Accept: 'application/json',
                        },
                    }
                );

                const result = await response.json();


                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Unable to load event types.'
                    );
                }

                if (!cancelled) {
                    setEventTypes(result.data || []);
                }
            } catch (error) {
                if (!cancelled) {
                    setApiError(error.message || 'Unable to load event types.');
                }
            } finally {
                if (!cancelled) {
                    setLoadingTypes(false);
                }
            }
        }

        loadEventTypes();

        return () => {
            cancelled = true;
        };
    }, []);



    useEffect(() => {
        let cancelled = false;

        const loadCategories = async () => {
            const typeId = formData.event_type_id;
            const savedEventMasterId = formData.event_master_id;

            if (!typeId) {
                setEventCategories([]);
                setSelectedEvent(null);
                setLoadingCategories(false);
                return;
            }

            setLoadingCategories(true);
            setApiError('');

            try {
                const response = await fetch(
                    `${API_BASE}/event-activity-types/${typeId}/categories`,
                    {
                        method: 'GET',
                        credentials: 'include',
                        headers: {
                            Accept: 'application/json',
                        },
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Unable to load categories.'
                    );
                }

                if (cancelled) return;

                const categories = Array.isArray(result.data)
                    ? result.data
                    : [];

                setEventCategories(categories);

                const selected = categories.find(
                    (item) =>
                        String(item.event_master_id) ===
                        String(savedEventMasterId)
                );

                setSelectedEvent(selected || null);

                if (selected) {
                    setFormData((previous) => ({
                        ...previous,
                        event_category_id: String(
                            selected.event_category_id
                        ),
                    }));
                }
            } catch (error) {
                if (!cancelled) {
                    setEventCategories([]);
                    setSelectedEvent(null);
                    setApiError(
                        error.message || 'Unable to load categories.'
                    );
                }
            } finally {
                if (!cancelled) {
                    setLoadingCategories(false);
                }
            }
        };

        loadCategories();

        return () => {
            cancelled = true;
        };
    }, [formData.event_type_id, formData.event_master_id]);


    useEffect(() => {
        const search = learnerSearch.trim();

        if (search.length < 2 || !showSuggestions) {
            setLearnerSuggestions([]);
            setLoadingLearners(false);
            return;
        }

        const controller = new AbortController();

        const timer = setTimeout(async () => {
            setLoadingLearners(true);

            try {
                const response = await fetch(
                    `${API_BASE}/learners/search?q=${encodeURIComponent(search)}`,
                    {
                        credentials: 'include',
                        headers: {
                            Accept: 'application/json',
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

                setLearnerSuggestions(result.data || []);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Learner search failed:', error);
                    setLearnerSuggestions([]);
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


    const handleEventTypeChange = (typeId) => {
        setFormData((previous) => ({
            ...previous,
            event_type_id: typeId,
            event_category_id: '',
            event_master_id: '',
            document_1: null,
            document_2: null,
            document_3: null,
        }));

        setSelectedEvent(null);
        setEventCategories([]);
        setApiError('');

        setErrors((previous) => ({
            ...previous,
            event_type_id: '',
            event_category_id: '',
        }));
    };

    const handleCategoryChange = (eventMasterId) => {
        const selected = eventCategories.find(
            (item) => String(item.event_master_id) === String(eventMasterId)
        );

        setSelectedEvent(selected || null);

        setFormData((previous) => ({
            ...previous,
            event_master_id: eventMasterId,
            event_category_id: selected
                ? String(selected.event_category_id)
                : '',
            document_1: null,
            document_2: null,
            document_3: null,
        }));

        setErrors((previous) => ({
            ...previous,
            event_category_id: '',
        }));
    };



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

    const selectLearner = (learner) => {
        setFormData((previous) => ({
            ...previous,
            learner_id: learner.id,
            beneficiary_name: learner.name,
            beneficiary_phone_number: learner.phone,
        }));

        setLearnerSearch(learner.name);
        setShowSuggestions(false);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        const newErrors = {};

        if (currentWorkflowStatus === 'submitted') {
            setToast({
                type: 'error',
                title: 'Already Submitted',
                message:
                    'You have already submitted this event. Please wait for admin review.',
            });
            return;
        }


        if (!formData.event_type_id) {
            newErrors.event_type_id = 'Please select Event Type.';
        }

        if (!formData.event_master_id) {
            newErrors.event_category_id = 'Please select Event Category.';
        }

        if (!formData.beneficiary_name.trim()) {
            newErrors.beneficiary_name = 'Please select a learner.';
        }

        if (!formData.beneficiary_phone_number.trim()) {
            newErrors.beneficiary_phone_number =
                'Beneficiary phone number is required.';
        }

        if (!formData.learner_id) {
            newErrors.beneficiary_name = 'Please select a learner from the suggestions.';
        }

        if (!formData.workflow_status) {
            newErrors.workflow_status = 'Please select Workflow Status.';
        }

        setErrors(newErrors);

        if (Object.keys(newErrors).length > 0) {
            return;
        }

        const payload = new FormData();

        payload.append('learner_id', formData.learner_id); // Add this line
        payload.append('event_master_id', formData.event_master_id);
        payload.append('event_master_type_id', formData.event_type_id);
        payload.append('event_category_id', formData.event_category_id);
        payload.append('beneficiary_name', formData.beneficiary_name.trim());
        payload.append(
            'beneficiary_phone_number',
            formData.beneficiary_phone_number.trim()
        );
        payload.append('monthly_income', formData.monthly_income || '');
        payload.append('comment', formData.comment || '');

        ['document_1', 'document_2', 'document_3'].forEach((field) => {
            if (formData[field] instanceof File) {
                payload.append(field, formData[field]);
            }
        });

        setSaving(true);
        setApiError('');
        try {
            const response = await fetch(
                `${API_BASE}/event-activities/${activityId}`,
                {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        Accept: 'application/json',
                        'X-HTTP-Method-Override': 'PUT',
                    },
                    body: (() => {
                        payload.append('_method', 'PUT');
                        return payload;
                    })(),
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {

                const message = result.message || 'Unable to save event activity.';

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
                    setToast({
                        type: "error",
                        title: "Update Failed",
                        message,
                    });
                }

                throw new Error(message);
            }

            setApiError('');
            setSuccessMessage(
                result.message || 'Your event activity details have been saved successfully.'
            );
           if (response.ok && result.success) {
                const message =
                    result.message || 'Event activity updated successfully.';

                if (onBack) {
                    onBack(message);
                }

                setToast({
                    type: 'success',
                    title: 'Success!',
                    message: result.message || 'Event activity updated successfully.',
                });

                return;
            }

        } catch (error) {
                setApiError(error.message || 'Unable to save event activity.');

                setToast({
                    type: 'error',
                    title: 'Update Failed',
                    message: error.message || 'Please try again.',
                });
            } finally {
                setSaving(false);
            }
    };


    const formatTimelineDate = (date) => {
    if (!date) return 'Date unavailable';

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) {
        return date;
    }

    return parsedDate.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};

const formatTimelineStatus = (status) => {
    if (!status) return "Unknown";

    return String(status)
        .replace(/[_-]+/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
};


const getTimelineStatusClass = (status) => {
    const normalizedStatus = String(status || "")
        .toLowerCase()
        .replace(/[\s-]+/g, "_");

    switch (normalizedStatus) {
        case "approved":
        case "accepted":
        case "completed":
            return "timeline-status-approved";

        case "rejected":
        case "declined":
            return "timeline-status-rejected";

        case "pending":
        case "under_review":
        case "open":
            return "timeline-status-pending";

        case "returned":
        case "return":
            return "timeline-status-returned";

        default:
            return "timeline-status-default";
    }
};


const getTimelineActionClass = (action) => {
    const normalizedAction = String(action || "")
        .toLowerCase()
        .replace(/[\s-]+/g, "_");

    if (normalizedAction.includes("approv") || normalizedAction.includes("accept")) {
        return "action-approved";
    }

    if (normalizedAction.includes("reject") || normalizedAction.includes("declin")) {
        return "action-rejected";
    }

    if (normalizedAction.includes("return")) {
        return "action-returned";
    }

    if (normalizedAction.includes("create") || normalizedAction.includes("submit")) {
        return "action-created";
    }

    if (normalizedAction.includes("update") || normalizedAction.includes("edit")) {
        return "action-updated";
    }

    if (normalizedAction.includes("delete") || normalizedAction.includes("remov")) {
        return "action-deleted";
    }

    return "action-default";
};

const getTimelineActor = (log) => {
    if (log.actor_name) return log.actor_name;
    if (log.user_name) return log.user_name;
    if (log.user_id) return `User ID: ${log.user_id}`;

    return 'Unknown User';
};



const renderDocumentField = (field, label) => (
        <div className="ea-form-field" key={field}>
            <label className="ea-form-label" htmlFor={field}>
                {label}
                <span className="ea-required"> *</span>
            </label>

            <div className="ea-input-icon-wrap">
                <Upload size={18} />

                <input
                    id={field}
                    name={field}
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    className="ea-form-control"
                    onChange={(e) =>
                        updateField(field, e.target.files?.[0] ?? null)
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
    );

const workflowStatuses = [
    { value: 'Draft', label: 'Draft' },
    { value: 'ReSubmitted', label: 'ReSubmitted' },
];

if (loading) {
    return (
        <section className="ea-page ea-edit-page">
            <div className="ea-form-card">
                Loading event details...
            </div>
        </section>
    );
}

if (loadError) {
    return (
        <section className="ea-page ea-edit-page">
            <div className="ea-form-card">
            <p className="ea-error-text">{loadError}</p>
            <button
                type="button"
                className="ea-back-button"
                onClick={() => onBack?.()}
            >
                <ArrowLeft size={17} />
                <span>Back to Event List</span>
            </button>
            </div>
        </section>
    );
}

    return (
        <section className="ea-page ea-edit-page">
            <header className="ea-header">
                <div className="ea-header-left">
                    <div>
                        <h1>Edit Event Activity</h1>
                        <p>
                            Update beneficiary details and event information.
                        </p>
                    </div>
                </div>

                <div className="ea-header-actions">
                    <div className="ea-record-badge">
                        <ClipboardList size={16} />
                        Activity #{activityId}
                    </div>

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



                {successMessage && (
                    <div
                        className="ea-success-banner"
                        role="status"
                        aria-live="polite"
                    >
                        <div className="ea-success-icon">
                            <CheckCircle2 size={22} strokeWidth={2.2} />
                        </div>

                        <div className="ea-success-content">
                            <h3 className="ea-success-title">
                                Activity Updated Successfully
                            </h3>

                            <p className="ea-success-description">
                                {successMessage}
                            </p>
                        </div>

                        <button
                            type="button"
                            className="ea-success-close"
                            onClick={() => setSuccessMessage('')}
                            aria-label="Dismiss success message"
                        >
                            <X size={18} />
                        </button>
                    </div>
                )}


            <form className="ea-edit-form" onSubmit={handleSubmit}>
                {/* Event Information */}
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
                                onChange={(e) => handleEventTypeChange(e.target.value)}
                                className="ea-form-control"
                                disabled={loadingTypes}
                            >
                                <option value="">
                                    {loadingTypes ? 'Loading event types...' : 'Select Event Type'}
                                </option>

                                {eventTypes.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>

                            {errors.event_type_id && (
                                <small className="ea-error-text">
                                    {errors.event_type_id}
                                </small>
                            )}
                        </div>

                        <div className="ea-form-field">
                            <label className="ea-form-label">
                                Event Category <span className="ea-required">*</span>
                            </label>

                            <select
                                value={formData.event_master_id}
                                onChange={(e) => handleCategoryChange(e.target.value)}
                                className="ea-form-control"
                                disabled={!formData.event_type_id || loadingCategories}
                            >
                                <option value="">
                                    {loadingCategories
                                        ? 'Loading categories...'
                                        : 'Select Event Category'}
                                </option>

                                {eventCategories.map((item) => (
                                    <option
                                        key={item.event_master_id}
                                        value={String(item.event_master_id)}
                                    >
                                        {item.name}
                                    </option>
                                ))}
                            </select>

                            {errors.event_category_id && (
                                <small className="ea-error-text">
                                    {errors.event_category_id}
                                </small>
                            )}
                        </div>
                    </div>
                </div>

                {/* Beneficiary Information */}
                <div className="ea-form-card">
                    <div className="ea-form-section-heading">
                        <div className="ea-section-icon">
                            <UserRound size={19} />
                        </div>
                        <div>
                            <h2>Beneficiary Information</h2>
                            <p>Search learner by name or mobile number.</p>
                        </div>
                    </div>

                    <div className="ea-form-grid">
                        <div className="ea-form-field ea-autocomplete-field">
                            <label className="ea-form-label">
                                Beneficiary Name
                                <span className="ea-required">*</span>
                            </label>

                            <div className="ea-input-icon-wrap">
                                <Search size={18} />
                                <input
                                    type="text"
                                    value={learnerSearch}
                                    placeholder="Search learner name or phone..."
                                    className="ea-form-control"
                                    autoComplete="off"
                                    onChange={(e) => {
                                        const value = e.target.value;
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
                                        learnerSuggestions.map((learner) => (
                                            <button
                                                type="button"
                                                key={learner.id}
                                                className="ea-learner-option"
                                                onClick={() => selectLearner(learner)}
                                            >
                                                <span className="ea-learner-avatar">
                                                    {learner.name.charAt(0).toUpperCase()}
                                                </span>

                                                <span className="ea-learner-info">
                                                    <strong>{learner.name}</strong>
                                                    <small>{learner.phone || 'Mobile not available'}</small>
                                                </span>
                                            </button>
                                        ))
                                    ) : (
                                        <div className="ea-no-learners">
                                            No learners found
                                        </div>
                                    )}
                                </div>
                            )}

                            {errors.beneficiary_name && (
                                <small className="ea-error-text">
                                    {errors.beneficiary_name}
                                </small>
                            )}
                        </div>

                        <div className="ea-form-field">
                            <label className="ea-form-label">
                                <Phone size={15} />
                                Beneficiary Phone Number
                            </label>

                            <input
                                type="tel"
                                value={formData.beneficiary_phone_number}
                                className="ea-form-control ea-readonly-control"
                                placeholder="Auto-filled after selecting learner"
                                readOnly
                            />
                            <small className="ea-field-hint">
                                Automatically filled from selected learner.
                            </small>
                        </div>

                        <div className="ea-form-field">
                            <label className="ea-form-label">
                                <IndianRupee size={15} />
                                Monthly Salary / Income (INR)
                            </label>

                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value={formData.monthly_income}
                                onChange={(e) =>
                                    updateField('monthly_income', e.target.value)
                                }
                                className="ea-form-control"
                                placeholder="Enter monthly income"
                            />
                        </div>
                    </div>
                </div>
                {/* Documents */}
                <div className="ea-form-card">
                    <div className="ea-form-section-heading">
                        <div className="ea-section-icon">
                            <FileText size={19} />
                        </div>

                        <div>
                            <h2>Supporting Documents</h2>
                            <p>
                                Upload documents required for this event category.
                            </p>
                        </div>
                    </div>

                    <div className="ea-form-grid">
                        {documentFields.length > 0 ? (
                            documentFields.map((item) => (
                                <div key={item.field} className="ea-document-item">
                                    {renderDocumentField(item.field, item.label)}

                                    {existingDocuments[item.field] && (
                                        <a
                                            href={`/storage/${existingDocuments[item.field]}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="ea-existing-document-link"
                                        >
                                            <FileText size={14} />
                                            View existing document
                                        </a>
                                    )}
                                </div>
                            ))
                        ) : (
                            <p className="ea-field-hint">
                                Please select an Event Category to view required documents.
                            </p>
                        )}
                    </div>

                    <p className="ea-document-note">
                        Document requirements will be configured according to
                        the selected Event Category after API integration.
                    </p>
                </div>

                {/* Workflow and Comment */}
                <div className="ea-form-card">
                    <div className="ea-form-section-heading">
                        <div className="ea-section-icon">
                            <MessageSquare size={19} />
                        </div>
                        <div>
                            <h2>Workflow & Comments</h2>
                            <p>Add a comment and select the workflow status.</p>
                        </div>
                    </div>

                    <div className="ea-form-grid">
                        <div className="ea-form-field">
                            <label className="ea-form-label">
                                Choose Workflow Status
                                <span className="ea-required">*</span>
                            </label>

                            <select
                                value={formData.workflow_status}
                                onChange={(e) =>
                                    updateField('workflow_status', e.target.value)
                                }
                                className="ea-form-control"
                            >
                                <option value="">Select Workflow Status</option>
                                {workflowStatuses.map((item) => (
                                    <option key={item.value} value={item.value}>
                                        {item.label}
                                    </option>
                                ))}
                            </select>

                            {errors.workflow_status && (
                                <small className="ea-error-text">
                                    {errors.workflow_status}
                                </small>
                            )}
                        </div>

                        <div className="ea-form-field ea-comment-field">
                            <label className="ea-form-label">Comment</label>

                            <input
                                value={formData.comment}
                                onChange={(e) =>
                                    updateField('comment', e.target.value)
                                }
                                className="ea-form-control"
                                placeholder="Enter comment or additional information..."
                            />
                        </div>
                    </div>
                </div>

                {/* Actions */}
                <div className="ea-form-actions">
                   <button
                        type="button"
                        className="ea-cancel-button"
                        onClick={() => onBack?.()}
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        className="ea-submit-button"
                        disabled={saving}
                    >
                        <Save size={17} />
                        {saving ? 'Saving...' : 'Save Event'}
                    </button>
                </div>
            </form>
            {apiError && (
                <p className="ea-error-text" role="alert">
                    {apiError}
                </p>
            )}



                {/* Activity Timeline / Audit Trail */}
                <section className="ea-form-card ea-timeline-card">
                    <div className="ea-form-section-heading">
                        <div className="ea-section-icon">
                            <ClipboardList size={19} />
                        </div>

                        <div>
                            <h2>Activity Timeline</h2>
                            <p>
                                Audit trail of status changes and activity updates.
                            </p>
                        </div>

                        <span className="ea-timeline-count">
                            {activityLogs.length} {activityLogs.length === 1 ? 'Entry' : 'Entries'}
                        </span>
                    </div>

                    {loadingLogs ? (
                        <div className="ea-timeline-empty">
                            Loading activity history...
                        </div>
                    ) : activityLogs.length > 0 ? (
                        <div className="ea-timeline">
                            {activityLogs.map((log, index) => {
                                const oldStatus = log.old_status;
                                const newStatus = log.new_status;

                                const hasStatusChange =
                                    Boolean(oldStatus) || Boolean(newStatus);

                                const action = String(
                                    log.action || 'activity_updated'
                                )
                                    .replace(/_/g, ' ')
                                    .replace(/\b\w/g, (char) => char.toUpperCase());

                                return (
                                    <article
                                        className="ea-timeline-item"
                                        key={log.id ?? `${log.created_at}-${index}`}
                                    >
                                        <div className="ea-timeline-marker">
                                            <span className="ea-timeline-dot" />
                                            {index !== activityLogs.length - 1 && (
                                                <span className="ea-timeline-line" />
                                            )}
                                        </div>

                                        <div className="ea-timeline-content">
                                            <div className="ea-timeline-top">
                                                <div className="ea-timeline-main">
                                                    <h3 className={`timeline-action-badge ${getTimelineActionClass(action)}`}>
                                                        {formatTimelineStatus(action)}
                                                    </h3>

                                                    {hasStatusChange && (
                                                        <div className="ea-timeline-transition">
                                                            <span
                                                                className={`ea-timeline-status ${getTimelineStatusClass(oldStatus)}`}
                                                            >
                                                                {formatTimelineStatus(oldStatus)}
                                                            </span>

                                                            <span
                                                                className="ea-timeline-arrow"
                                                                aria-label="changed to"
                                                            >
                                                                →
                                                            </span>

                                                            <span
                                                                className={`ea-timeline-status ${getTimelineStatusClass(newStatus)}`}
                                                            >
                                                                {formatTimelineStatus(newStatus)}
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>

                                                <time className="ea-timeline-date">
                                                    {formatTimelineDate(log.created_at)}
                                                </time>
                                            </div>

                                            <p className="ea-timeline-actor">
                                                Updated by <strong>{getTimelineActor(log)}</strong>
                                                {log.user_type && (
                                                    <span> ({log.user_type})</span>
                                                )}
                                            </p>

                                            {log.comment && (
                                                <div className="ea-timeline-comment">
                                                    <span className="ea-timeline-comment-label">
                                                        Comment
                                                    </span>
                                                    <p>{log.comment}</p>
                                                </div>
                                            )}
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="ea-timeline-empty">
                            <ClipboardList size={26} />
                            <strong>No activity recorded yet</strong>
                            <p>Status updates and comments will appear here when available.</p>
                        </div>
                    )}
                </section>

        </section>
    );
}
