<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\EventTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Services\ExportService;

class EventTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        if (!$partner || $partner->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized partner.',
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

        /*
        |--------------------------------------------------------------------------
        | Base Query - Partner Data Isolation
        |--------------------------------------------------------------------------
        */

        $baseQuery = EventTransaction::query()
            ->where('partner_id', $partner->id);

        /*
        |--------------------------------------------------------------------------
        | Overall Summary (Independent Of Search Filters)
        |--------------------------------------------------------------------------
        */

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE WHEN LOWER(review_status) IN ('approved', 'accepted') THEN 1 ELSE 0 END) as approved"
            )
            ->selectRaw(
                "SUM(CASE WHEN LOWER(review_status) IN ('pending', 'under_review', 'under review') THEN 1 ELSE 0 END) as pending"
            )
            ->selectRaw('COALESCE(SUM(event_value), 0) as total_value')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Search And Advanced Filters
        |--------------------------------------------------------------------------
        */

        $query = clone $baseQuery;

        if ($search = trim($validated['search'] ?? '')) {
            $query->where(function ($q) use ($search) {
                $q->where('beneficiary_name', 'LIKE', "%{$search}%")
                    ->orWhere('beneficiary_phone_number', 'LIKE', "%{$search}%")
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

        /*
        |--------------------------------------------------------------------------
        | Paginated Listing
        |--------------------------------------------------------------------------
        */

        $transactions = $query
            ->select([
                'id',
                'learner_id',
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

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

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
     * Queue an Excel export containing all partner event transactions
     * matching the submitted main search and advanced filters.
     */
    public function export(Request $request, ExportService $exportService): JsonResponse
    {
        $partner = $request->user('partner');

        if (!$partner || $partner->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized partner.',
            ], 403);
        }

        $validated = $request->validate([
            'filters' => ['sometimes', 'array'],

            'filters.search' => ['nullable', 'string', 'max:255'],
            'filters.beneficiary_name' => ['nullable', 'string', 'max:255'],
            'filters.beneficiary_phone_number' => ['nullable', 'string', 'max:255'],
            'filters.event_type_name' => ['nullable', 'string', 'max:255'],
            'filters.event_category_name' => ['nullable', 'string', 'max:255'],
            'filters.event_master_type_id' => ['nullable', 'string', 'max:255'],
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

        $export = $exportService->create($partner, [
            'type' => 'transactions',
            'scope_type' => 'partner',
            'scope_id' => $partner->id,
            'title' => 'Partner Event Transactions Export',
            'filters' => $filters,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Excel export request queued. Check Downloads for the file.',
            'export_id' => $export->id,
            'status' => $export->status,
        ], 202);
    }


    /*
    |--------------------------------------------------------------------------
    | Event Transaction Details
    |--------------------------------------------------------------------------
    */

    public function show(Request $request, int $id): JsonResponse
    {
        $partner = $request->user('partner');

        if (!$partner || $partner->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized partner.',
            ], 403);
        }

        $transaction = EventTransaction::query()
         ->with([
                    'fieldAgent',
                    'partner',
                    'partnerCenter',
                    'fieldType',
                    'industryType',
                ])
            ->where('partner_id', $partner->id)
            ->whereKey($id)
            ->firstOrFail();

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

        /*
        |--------------------------------------------------------------------------
        | Related Entity Names
        |--------------------------------------------------------------------------
        */

        $data['field_agent_name'] = $transaction->fieldAgent?->name;

        $data['field_agent_sakhi_id'] = $transaction->fieldAgent?->sakhi_id;

        $data['partner_name'] = $transaction->partner?->name;

        $data['partner_center_name'] = $transaction->partnerCenter?->name;

        $data['field_type_name'] = $transaction->fieldType?->name;

        $data['industry_type_name'] = $transaction->industryType?->name;

        /*
        |--------------------------------------------------------------------------
        | Documents
        |--------------------------------------------------------------------------
        | Return metadata only until secure document download URLs
        | are implemented. Never expose raw private storage paths.
        */

        $data['documents'] = collect([
            ['key' => 'uploaded_doc_1', 'label' => 'Document 1'],
            ['key' => 'uploaded_doc_2', 'label' => 'Document 2'],
            ['key' => 'uploaded_doc_3', 'label' => 'Document 3'],
        ])
            ->filter(fn ($doc) => filled($transaction->{$doc['key']}))
            ->map(function ($doc) use ($transaction) {

                $path = $transaction->{$doc['key']};

                return [
                    'key' => $doc['key'],
                    'name' => $doc['label'],
                    'available' => Storage::disk('public')->exists($path),
                    'url' => Storage::disk('public')->exists($path)
                        ? Storage::disk('public')->url($path)
                        : null,
                ];
            })
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Current Timeline
        |--------------------------------------------------------------------------
        | These are known milestones, not historical status changes.
        */

        $timeline = [];

        if ($transaction->event_create_date || $transaction->created_at) {
            $timeline[] = [
                'id' => 'created',
                'title' => 'Event Created',
                'description' => 'Event transaction was created.',
                'date' => $transaction->event_create_date
                    ? $transaction->event_create_date->format('Y-m-d')
                    : $transaction->created_at?->toIso8601String(),
                'type' => 'created',
            ];
        }

        if ($transaction->event_date_submitted) {
            $timeline[] = [
                'id' => 'submitted',
                'title' => 'Event Submitted',
                'description' => 'Event submission date.',
                'date' => $transaction->event_date_submitted->format('Y-m-d'),
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
            ? [
                [
                    'id' => 'latest',
                    'comment' => $transaction->last_comment,
                    'author' => null,
                    'created_at' => null,
                    'is_latest' => true,
                ],
            ]
            : [];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

}
