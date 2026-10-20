import { useCallback, useEffect, useState } from 'react';
import { GraduationCap, UsersRound, CalendarDays, ChevronRight } from 'lucide-react';

const formatCount = (value) => typeof value === 'number' && Number.isFinite(value)
    ? new Intl.NumberFormat('en-IN').format(value) : '—';

export default function PartnerSummary({ summaryUrl }) {
    const [summary, setSummary] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [retry, setRetry] = useState(0);

    const reload = useCallback(() => setRetry((current) => current + 1), []);

    useEffect(() => {
        const controller = new AbortController();
        async function load() {
            setLoading(true);
            setError('');
            try {
                const response = await fetch(summaryUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error(response.status === 401 || response.status === 419
                    ? 'Your session has expired. Please sign in again.' : 'Unable to load dashboard summary.');
                const payload = await response.json();
                if (!payload.success || !payload.data) throw new Error('Unexpected summary response.');
                if (!controller.signal.aborted) setSummary(payload.data);
            } catch (err) {
                if (!controller.signal.aborted) setError(err.message || 'Unable to load dashboard summary.');
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        }
        load();
        return () => controller.abort();
    }, [summaryUrl, retry]);

    const cards = [
        { label: 'Total Learners', key: 'total_learners', color: 'blue', Icon: GraduationCap },
        { label: 'Field Agents', key: 'total_field_agents', color: 'green', Icon: UsersRound },
        { label: 'Event Transactions', key: 'total_event_transactions', color: 'yellow', Icon: CalendarDays },
    ];
    return <section aria-label="Partner dashboard summary" aria-busy={loading}>
        {error && <div className="pd-summary-error" role="alert">{error}
            <button type="button" className="pd-summary-retry" onClick={reload}>Retry</button>
        </div>}
        <div className="pd-cards">
            {cards.map(({ label, key, color, Icon }) => <article className={`pd-card ${color}`} key={key}>
                <span className="pd-icon"><Icon size={23} aria-hidden="true" /></span>
                <div><small>{label}</small><strong aria-label={`${label}: ${loading ? 'Loading' : formatCount(summary?.[key])}`}>
                    {loading ? '…' : formatCount(summary?.[key])}
                </strong></div>
                <ChevronRight className="pd-arrow" size={16} aria-hidden="true" />
            </article>)}
        </div>
    </section>;
}
