import React from 'react';

/**
 * LearnerStatusDots Component
 *
 * Displays exactly three status circles for Skills, Job, and Social
 * Protection using the calculated activity statuses returned by the API.
 *
 * Color mapping:
 * Green  = Completed / Accepted
 * Blue   = Submitted
 * Orange = Pending / Under Review / Action Required
 * Red    = Rejected
 * Gray   = Not Started / Missing status
 */
const LearnerStatusDots = ({ learner }) => {
    /**
     * Convert an activity status into its corresponding CSS class.
     *
     * The backend determines whether an activity is submitted by checking
     * both review_status and workflow_status. Therefore, "open" by itself
     * must not be treated as submitted.
     *
     * @param {string|null|undefined} status Calculated activity status.
     * @returns {string} CSS class for the status circle.
     */
    const getStatusClass = (status) => {
        const value = String(status ?? '')
            .trim()
            .toLowerCase()
            .replace(/[_-]+/g, ' ');

        switch (value) {
            case 'accepted':
            case 'approved':
            case 'completed':
                return 'accepted';

            case 'submitted':
                return 'submitted';

            case 'pending':
            case 'under review':
            case 'action required':
            case 'return':
            case 'returned':
                return 'action-required';

            case 'rejected':
                return 'rejected';

            case 'not started':
            case 'not_started':
            case '':
            default:
                return 'not-started';
        }
    };

    /**
     * Build the three activity indicators from the learner API response.
     *
     * @type {Array<{key: string, label: string, status: string}>}
     */
    const activities = [
        {
            key: 'skills',
            label: 'Skills',
            status:
                learner?.skills_status ??
                learner?.skills?.status ??
                'not_started',
        },
        {
            key: 'job',
            label: 'Job',
            status:
                learner?.job_status ??
                learner?.job?.status ??
                'not_started',
        },
        {
            key: 'social-protection',
            label: 'Social Protection',
            status:
                learner?.social_protection_status ??
                learner?.social_protection?.status ??
                'not_started',
        },
    ];

    return (
        <div className="learner-status-dots">
            {activities.map(({ key, label, status }) => {
                const statusClass = getStatusClass(status);
                const displayStatus = String(status)
                    .replace(/[_-]+/g, ' ');

                return (
                    <span
                        key={key}
                        className={`learner-status-dot ${statusClass}`}
                        title={`${label}: ${displayStatus}`}
                        role="img"
                        aria-label={`${label}: ${displayStatus}`}
                    />
                );
            })}
        </div>
    );
};

export default LearnerStatusDots;
