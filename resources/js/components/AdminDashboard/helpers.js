
export function formatStatus(value) {
    return String(value || '—')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

export function statusClass(value) {
    return String(value || 'unknown')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-');
}

export function exportLearners(rows) {
    const columns = [
        'Name',
        'Location',
        'Program',
        'Stage',
        'Updated',
    ];

    const values = rows.map((learner) => [
        learner.first_name ?? '',
        learner.location ?? '',
        learner.program_code ?? '',
        learner.stage ?? '',
        learner.source_created_at ?? '',
    ]);

    const csv = [columns, ...values]
        .map((row) =>
            row
                .map((value) =>
                    `"${String(value).replaceAll('"', '""')}"`
                )
                .join(',')
        )
        .join('\r\n');

    const url = URL.createObjectURL(
        new Blob(['\uFEFF', csv], {
            type: 'text/csv;charset=utf-8',
        })
    );

    const link = document.createElement('a');
    link.href = url;
    link.download = 'youthhub-learners.csv';
    link.click();

    URL.revokeObjectURL(url);
}
