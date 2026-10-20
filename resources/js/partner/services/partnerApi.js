export async function getDashboardSummary({ signal } = {}) {
    const response = await fetch('/api/partner/dashboard/summary', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        signal,
    });

    if (!response.ok) {
        throw new Error(
            response.status === 401
                ? 'Your session has expired. Please sign in again.'
                : `Unable to load dashboard (${response.status}).`
        );
    }

    const json = await response.json();

    if (!json.success || !json.data) {
        throw new Error('Unexpected dashboard response.');
    }

    return json.data;
}
