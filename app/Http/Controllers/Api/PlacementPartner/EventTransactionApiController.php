<?php

namespace App\Http\Controllers\Api\PlacementPartner;

use App\Http\Controllers\Controller;
use App\Models\EventTransaction;
use App\Models\Export;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class EventTransactionApiController extends Controller
{
    /**
     * Only transactions belonging to this Placement Partner's
     * assigned Field Agents can be accessed.
     */
    private function baseQuery(Request $request)
    {
        $placementPartner = $request->user('partner_placement');

        $agentIds = DB::table('field_agents')
            ->where('placement_partner_user_id', $placementPartner->id)
            ->select('id');

        return EventTransaction::query()
            ->whereIn('field_agent_id', $agentIds);
    }

    private function authorizedPartner(Request $request)
    {
        $placementPartner = $request->user('partner_placement');

        if (!$placementPartner || $placementPartner->status !== 'active') {
            return null;
        }

        return $placementPartner;
    }

    /**
     * GET /api/partner-placement/event-transactions
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->authorizedPartner($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Placement Partner.',
            ], 403);
        }

        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],

            'search' => ['nullable', 'string', 'max:255'],
            'beneficiary_name' => ['nullable', 'string', 'max:255'],
            'beneficiary_phone_number' => ['nullable', 'string', 'max:255'],
            'event_type_name' => ['nullable', 'string', 'max:255'],
            'event_category_name' => ['nullable', 'string', 'max:255'],
            'event_master_type_id' => ['nullable', 'string', 'max:255'],
            'review_status' => ['nullable', 'string', 'max:255'],
            'workflow_status' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);

        // Scoped base query: assigned Field Agents only.
        $baseQuery = $this->baseQuery($request);

        // Summary remains independent of search and advanced filters.
        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN LOWER(review_status) IN ('approved', 'accepted')
                        THEN 1 ELSE 0
                    END
                ) as approved
            ")
            ->selectRaw("
                SUM(
                    CASE
                        WHEN LOWER(review_status) IN
                            ('pending', 'under_review', 'under review')
                        THEN 1 ELSE 0
                    END
                ) as pending
            ")
            ->selectRaw('COALESCE(SUM(event_value), 0) as total_value')
            ->first();

        $query = clone $baseQuery;

        if ($search = trim($validated['search'] ?? '')) {
            $query->where(function ($q) use ($search) {
                $q->where('beneficiary_name', 'LIKE', "%{$search}%")
                    ->orWhere(
                        'beneficiary_phone_number',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere('event_type_name', 'LIKE', "%{$search}%")
                    ->orWhere('event_category_name', 'LIKE', "%{$search}%");

                if (ctype_digit($search)) {
                    $q->orWhere('id', $search);
                }
            });
        }

        foreach ([
            'beneficiary_name',
            'beneficiary_phone_number',
            'event_type_name',
            'event_category_name',
        ] as $field) {
            if (filled($validated[$field] ?? null)) {
                $query->where(
                    $field,
                    'LIKE',
                    '%' . trim($validated[$field]) . '%'
                );
            }
        }

        foreach ([
            'event_master_type_id',
            'review_status',
            'workflow_status',
        ] as $field) {
            if (filled($validated[$field] ?? null)) {
                $query->where($field, $validated[$field]);
            }
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate(
                'event_date_submitted',
                '>=',
                $validated['date_from']
            );
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate(
                'event_date_submitted',
                '<=',
                $validated['date_to']
            );
        }

        $transactions = $query
            ->select([
                'id',
                'learner_id',
                'field_agent_id',
                'beneficiary_name',
                'beneficiary_phone_number',
                'event_master_type_id',
                'event_type_name',
                'event_category_name',
                'event_value',
                'event_date_submitted',
                'review_status',
                'workflow_status',
                'created_at',
            ])
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'summary' => [
                'total' => (int) ($summary->total ?? 0),
                'approved' => (int) ($summary->approved ?? 0),
                'pending' => (int) ($summary->pending ?? 0),
                'total_value' => (float) ($summary->total_value ?? 0),
            ],
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * POST /api/partner-placement/event-transactions/export
     *
     * Export all matching records, not only the current page.
     */
    public function export(
        Request $request,
        ExportService $exportService
    ): JsonResponse {
        $placementPartner = $this->authorizedPartner($request);

        if (!$placementPartner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Placement Partner.',
            ], 403);
        }

        $validated = $request->validate([
            'filters' => ['sometimes', 'array'],
            'filters.search' => ['nullable', 'string', 'max:255'],
            'filters.beneficiary_name' => ['nullable', 'string', 'max:255'],
            'filters.beneficiary_phone_number' => [
                'nullable',
                'string',
                'max:255',
            ],
            'filters.event_type_name' => ['nullable', 'string', 'max:255'],
            'filters.event_category_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'filters.event_master_type_id' => [
                'nullable',
                'string',
                'max:255',
            ],
            'filters.review_status' => ['nullable', 'string', 'max:255'],
            'filters.workflow_status' => ['nullable', 'string', 'max:255'],
            'filters.date_from' => ['nullable', 'date_format:Y-m-d'],
            'filters.date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:filters.date_from',
            ],
        ]);

        $filters = array_filter(
            $validated['filters'] ?? [],
            fn ($value) => $value !== null && $value !== ''
        );

        /*
         * ExportService's transaction exporter must recognize
         * scope_type = placement_partner and filter records by the
         * placement_partner_user_id assigned Field Agent relationship.
         */
        $export = $exportService->create($placementPartner, [
            'type' => 'transactions',
            'scope_type' => 'placement_partner',
            'scope_id' => $placementPartner->id,
            'title' => 'Placement Partner Event Transactions Export',
            'filters' => $filters,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Excel export request queued. Check Downloads for the file.',
            'export_id' => $export->id,
            'status' => $export->status,
        ], 202);
    }

    /**
     * GET /api/partner-placement/event-transactions/exports/{exportId}
     */
    public function exportStatus(
        Request $request,
        int $exportId
    ): JsonResponse {
        $placementPartner = $this->authorizedPartner($request);

        if (!$placementPartner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Placement Partner.',
            ], 403);
        }

        $export = Export::query()
            ->whereKey($exportId)
            ->where(
                'requested_by_type',
                $placementPartner->getMorphClass()
            )
            ->where('requested_by_id', $placementPartner->id)
            ->first();

        if (!$export) {
            return response()->json([
                'success' => false,
                'message' => 'Export not found.',
            ], 404);
        }

        $total = (int) ($export->total_records ?? 0);
        $processed = (int) ($export->processed_records ?? 0);

        $progress = $export->status === 'completed'
            ? 100
            : ($total > 0
                ? min(99, (int) floor(($processed / $total) * 100))
                : 0);

        return response()->json([
            'success' => true,
            'export_id' => $export->id,
            'status' => $export->status,
            'total_records' => $total,
            'processed_records' => $processed,
            'progress_percent' => $progress,
            'estimated_seconds_remaining' =>
                $export->estimated_seconds_remaining,
            'error_message' => $export->status === 'failed'
                ? $export->error_message
                : null,
        ]);
    }

    /**
     * GET /api/partner-placement/event-transactions/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if (!$this->authorizedPartner($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Placement Partner.',
            ], 403);
        }

        // Use the same access scope for detail requests.
        $transaction = $this->baseQuery($request)
            ->with(['fieldAgent', 'fieldType', 'industryType'])
            ->whereKey($id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        $data = $transaction->only([
            'id',
            'learner_id',
            'partner_id',
            'partner_center_id',
            'partner_placement_user_id',
            'field_agent_id',
            'beneficiary_name',
            'beneficiary_phone_number',
            'event_master_id',
            'event_master_type_id',
            'event_type_name',
            'event_category_name',
            'event_create_date',
            'event_date_submitted',
            'event_value',
            'field_type_id',
            'industry_type_id',
            'review_status',
            'workflow_status',
            'last_comment',
            'status',
            'created_at',
            'updated_at',
        ]);

        $data['field_agent_name'] = $transaction->fieldAgent?->name;
        $data['field_agent_sakhi_id'] = $transaction->fieldAgent?->sakhi_id;
        $data['field_type_name'] = $transaction->fieldType?->name;
        $data['industry_type_name'] = $transaction->industryType?->name;

        $data['documents'] = collect([
            ['key' => 'uploaded_doc_1', 'label' => 'Document 1'],
            ['key' => 'uploaded_doc_2', 'label' => 'Document 2'],
            ['key' => 'uploaded_doc_3', 'label' => 'Document 3'],
        ])
            ->filter(fn ($doc) => filled($transaction->{$doc['key']}))
            ->map(function ($doc) use ($transaction) {
                $path = $transaction->{$doc['key']};
                $exists = Storage::disk('public')->exists($path);

                return [
                    'key' => $doc['key'],
                    'name' => $doc['label'],
                    'available' => $exists,
                    'url' => $exists
                        ? Storage::disk('public')->url($path)
                        : null,
                ];
            })
            ->values()
            ->all();

        $timeline = [];

        if ($transaction->event_create_date || $transaction->created_at) {
            $timeline[] = [
                'id' => 'created',
                'title' => 'Event Created',
                'description' => 'Event transaction was created.',
                'date' => $transaction->event_create_date
                    ? Carbon::parse($transaction->event_create_date)->format('Y-m-d')
                    : $transaction->created_at?->toIso8601String(),
                'type' => 'created',
            ];
        }

        if ($transaction->event_date_submitted) {
            $timeline[] = [
                'id' => 'submitted',
                'title' => 'Event Submitted',
                'description' => 'Event submission date.',
                'date' => Carbon::parse($transaction->event_date_submitted)->format('Y-m-d'),
                'type' => 'submitted',
            ];
        }

        $timeline[] = [
            'id' => 'current',
            'title' => 'Current Status',
            'description' => sprintf(
                'Review: %s | Workflow: %s',
                $transaction->review_status ?: 'Not Reviewed',
                $transaction->workflow_status ?: 'Unknown'
            ),
            'date' => null,
            'type' => 'current',
        ];

        $data['timeline'] = $timeline;

        $data['comments'] = filled($transaction->last_comment)
            ? [[
                'id' => 'latest',
                'comment' => $transaction->last_comment,
                'author' => null,
                'created_at' => null,
                'is_latest' => true,
            ]]
            : [];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
