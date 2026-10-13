
import {
    ArrowUpRight,
    CalendarDays,
    MoreHorizontal,
    Phone,
    UsersRound,
} from 'lucide-react';

import { formatStatus, statusClass } from './helpers';

export default function LatestEvents({
    events = [],
    loading = false,
}) {
    const openEvent = (eventId) => {
        window.location.href = `/admin/event-transactions/${eventId}`;
    };

    return (
        <article className="admin-panel admin-events" id="events">
            <div className="admin-panel__heading">
                <div>
                    <h2>Latest Events</h2>
                    <p>Latest event activity and review status</p>
                </div>

                <button
                    className="admin-icon-button"
                    aria-label="View all events"
                    title="View all events"
                    onClick={() => {
                        window.location.href = '/admin/event-transactions/';
                    }}
                >
                    <MoreHorizontal size={19} />
                </button>
            </div>

            <div className="admin-event-list">
                {events.map((event) => {
                    const eventDate =
                        event.event_create_date ||
                        event.event_date_submitted;

                    const date = eventDate
                        ? new Date(`${eventDate}T00:00:00`)
                        : null;

                    const validDate =
                        date && !Number.isNaN(date.getTime());

                    return (
                        <div
                            className="admin-event-card"
                            key={event.id}
                            role="link"
                            tabIndex={0}
                            onClick={() => openEvent(event.id)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    openEvent(event.id);
                                }
                            }}
                        >
                            <div className="admin-event-card__header">
                                <div className="admin-event-card__date">
                                    <CalendarDays size={16} />

                                    <span>
                                        {validDate
                                            ? date.toLocaleDateString('en-IN', {
                                                day: '2-digit',
                                                month: 'short',
                                                year: 'numeric',
                                            })
                                            : 'Date unavailable'}
                                    </span>
                                </div>

                                <span className="admin-event-card__id">
                                    #{event.sakhi_id || 'N/A'}
                                </span>
                            </div>

                            <h3 className="admin-event-card__title">
                                {event.field_agent_name || 'Field Agent'}
                            </h3>

                            <div className="admin-event-card__grid">
                                <div className="admin-event-card__section">
                                    <span className="admin-event-card__label">
                                        LEARNER DETAILS
                                    </span>

                                    <div className="admin-event-card__value">
                                        <UsersRound size={15} />
                                        <span>
                                            {event.learner_name ||
                                                'Learner not available'}
                                        </span>
                                    </div>

                                    <div className="admin-event-card__value">
                                        <Phone size={15} />
                                        <span>
                                            {event.learner_phone ||
                                                'Phone not available'}
                                        </span>
                                    </div>

                                    <div className="admin-event-card__location">
                                        {[event.district, event.state]
                                            .filter(Boolean)
                                            .join(', ') ||
                                            'Location unavailable'}
                                    </div>
                                </div>

                                <div className="admin-event-card__section">
                                    <span className="admin-event-card__label">
                                        EVENT DETAILS
                                    </span>

                                    <div className="admin-event-card__event-name">
                                        {event.event_name ||
                                            event.event_type_name ||
                                            'Event name unavailable'}
                                    </div>

                                    <div className="admin-event-card__category">
                                        {event.event_category_name ||
                                            'Category unavailable'}
                                    </div>

                                    <div className="admin-event-card__statuses">
                                        <span
                                            className={`admin-status-pill admin-status-pill--${statusClass(event.workflow_status)}`}
                                        >
                                            {formatStatus(event.workflow_status)}
                                        </span>

                                        <span
                                            className={`admin-status-pill admin-status-pill--${statusClass(event.review_status)}`}
                                        >
                                            {formatStatus(event.review_status)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    );
                })}

                {loading && (
                    <div className="admin-empty">Loading events...</div>
                )}

                {!loading && events.length === 0 && (
                    <div className="admin-empty">
                        No event records found.
                    </div>
                )}
            </div>

            <button
                className="admin-text-link"
                onClick={() => {
                    window.location.href = '/admin/event-transactions/';
                }}
            >
                View all events <ArrowUpRight size={14} />
            </button>
        </article>
    );
}
