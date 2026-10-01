<section class="learner-export-history">

    <div class="learner-section-heading">

        <div>
            <p class="learner-section-eyebrow">
                EXPORT CENTER
            </p>

            <h2>
                Export History
            </h2>

            <p>
                Your recent learner exports and downloadable files.
            </p>
        </div>

    </div>


    <div class="learner-export-table-wrap">

        <table class="learner-export-table">

            <thead>
                <tr>
                    <th>Export</th>
                    <th>Records</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Completed</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                @forelse($exports as $export)

                    <tr>

                        <td>

                            <div class="export-file">

                                <span class="export-file-icon">
                                    CSV
                                </span>

                                <div>

                                    <strong>
                                        {{ $export->file_name ?? 'Learner Export' }}
                                    </strong>

                                    <small>
                                        Export #{{ $export->id }}
                                    </small>

                                </div>

                            </div>

                        </td>

                        <td>
                            {{ number_format($export->total_records) }}
                        </td>

                        <td>

                            @if($export->status === 'queued')

                                <span class="export-status export-status--queued">
                                    <span></span>
                                    Queued
                                </span>

                            @elseif($export->status === 'processing')

                                <span class="export-status export-status--processing">
                                    <span></span>
                                    Processing
                                </span>

                            @elseif($export->status === 'completed')

                                <span class="export-status export-status--completed">
                                    <span></span>
                                    Ready
                                </span>

                            @else

                                <span class="export-status export-status--failed">
                                    <span></span>
                                    Failed
                                </span>

                            @endif

                        </td>

                        <td>
                            {{ $export->created_at?->format('d M Y, h:i A') }}
                        </td>

                        <td>
                            {{ $export->completed_at?->format('d M Y, h:i A') ?? '—' }}
                        </td>

                        <td>

                            @if($export->status === 'completed')

                                <a
                                    href="{{ route(
                                        'admin.learners.export.download',
                                        $export
                                    ) }}"
                                    class="learner-download-btn"
                                >
                                    ↓ Download
                                </a>

                            @elseif($export->status === 'processing')

                                <span class="export-waiting">
                                    Preparing...
                                </span>

                            @elseif($export->status === 'queued')

                                <span class="export-waiting">
                                    Waiting...
                                </span>

                            @else

                                <span
                                    class="export-error"
                                    title="{{ $export->error_message }}"
                                >
                                    Failed
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="export-empty"
                        >
                            No exports generated yet.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    @if($exports->hasPages())

        <div class="learner-export-pagination">

            {{ $exports->links() }}

        </div>

    @endif

</section>
