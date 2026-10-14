<?php

namespace App\Http\Controllers\Api\FieldAgent;

use App\Http\Controllers\Controller;
use App\Models\EventTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\EventMasterType;
use App\Models\EventCategoryMaster;
use App\Models\EventMaster;
use App\Services\EventTransactionService;
use DB;


class EventActivityController extends Controller
{

        protected EventTransactionService $service;

        public function __construct(EventTransactionService $service)
        {
            $this->service = $service;
        }

        public function searchLearners(Request $request): JsonResponse
        {
            $agent = $request->user('field_agent');

            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated Field Agent.',
                ], 401);
            }

            $search = trim((string) $request->query('q', ''));

            if (mb_strlen($search) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $learners = DB::table('learners')
                ->select([
                    'id',
                    'first_name',
                    'last_name',
                    'phone_number',
                ])
                ->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw(
                            "CONCAT_WS(' ', first_name, last_name) LIKE ?",
                            ["%{$search}%"]
                        )
                        ->orWhere('phone_number', 'like', "%{$search}%");
                })
                ->orderBy('first_name')
                ->limit(10)
                ->get()
                ->map(function ($learner) {
                    $firstName = trim((string) ($learner->first_name ?? ''));
                    $lastName = trim((string) ($learner->last_name ?? ''));

                    // Ignore placeholders such as NA, N/A and NULL.
                    if (in_array(strtoupper($lastName), ['NA', 'N/A', 'NULL', '-'], true)) {
                        $lastName = '';
                    }

                    return [
                        'id' => $learner->id,
                        'name' => trim($firstName . ' ' . $lastName),
                        'phone' => $learner->phone_number ?? '',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $learners,
            ]);
        }




    public function eventTypes(Request $request): JsonResponse
    {
        if (!$request->user('field_agent')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated Field Agent.',
            ], 401);
        }

        $types = EventMasterType::query()
            ->where('status', true)
            ->whereHas('eventMasters', function ($query) {
                $query->where('status', true)
                    ->whereHas('eventCategory', function ($categoryQuery) {
                        $categoryQuery->where('status', true);
                    });
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    public function eventCategories(
        Request $request,
        int $typeId
    ): JsonResponse {
        if (!$request->user('field_agent')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated Field Agent.',
            ], 401);
        }

        $typeExists = EventMasterType::query()
            ->where('status', true)
            ->whereKey($typeId)
            ->exists();

        if (!$typeExists) {
            return response()->json([
                'success' => false,
                'message' => 'Event type not found.',
            ], 404);
        }

        $events = EventMaster::query()
            ->with(['eventCategory:id,name'])
            ->where('event_master_type_id', $typeId)
            ->where('status', true)
            ->whereHas('eventCategory', function ($query) {
                $query->where('status', true);
            })
            ->select([
                'id',
                'event_master_type_id',
                'event_category_id',
                'event_name',
                'document_name_1',
                'document_name_2',
                'document_name_3',
            ])
            ->orderBy('event_category_id')
            ->orderBy('id')
            ->get()
            ->map(function ($event) {
                return [
                    'event_master_id' => $event->id,
                    'event_category_id' => $event->event_category_id,
                    'name' => $event->eventCategory?->name,
                    'document_name_1' => $event->document_name_1,
                    'document_name_2' => $event->document_name_2,
                    'document_name_3' => $event->document_name_3,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }


    public function options(Request $request): JsonResponse
    {
        if (!$request->user('field_agent')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated Field Agent.',
            ], 401);
        }

        $eventTypes = EventMasterType::query()
            ->where('status', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'event_types' => $eventTypes,
            ],
        ]);
    }

    public function categories(
        Request $request,
        int $typeId
    ): JsonResponse {
        if (!$request->user('field_agent')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated Field Agent.',
            ], 401);
        }

        $typeExists = EventMasterType::query()
            ->where('status', true)
            ->whereKey($typeId)
            ->exists();

        if (!$typeExists) {
            return response()->json([
                'success' => false,
                'message' => 'Event type not found.',
            ], 404);
        }

        $categories = EventCategoryMaster::query()
            ->where('event_master_type_id', $typeId)
            ->where('status', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }



/**
 * List activities belonging to the authenticated Field Agent.
 */

public function index(Request $request): JsonResponse
{
    $agent = $request->user('field_agent');

    if (!$agent) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated Field Agent.',
        ], 401);
    }

    // Base query: only this logged-in Field Agent's activities.
    $baseQuery = EventTransaction::query()
        ->where('field_agent_id', $agent->getAuthIdentifier());

    // Apply the same filters to both the listing and summary.
    if ($request->filled('review_status')) {
        $baseQuery->where(
            'review_status',
            $request->query('review_status')
        );
    }

    if ($request->filled('workflow_status')) {
        $baseQuery->where(
            'workflow_status',
            $request->query('workflow_status')
        );
    }

    /*
     * Summary counts are calculated across all matching records,
     * not just the current page.
     */
    $summaryQuery = clone $baseQuery;

    $summary = [
        'total' => (clone $summaryQuery)->count(),

        'approved' => (clone $summaryQuery)
            ->where('review_status', 'Accepted')
            ->count(),

        'rejected' => (clone $summaryQuery)
            ->where('review_status', 'Rejected')
            ->count(),

        'open' => (clone $summaryQuery)
            ->where('review_status', 'Open')
            ->count(),

        'draft' => (clone $summaryQuery)
            ->where('workflow_status', 'Draft')
            ->count(),
    ];

    // Paginated activity listing.
    $activities = (clone $baseQuery)
        ->select([
            'id',
            'learner_id',
            'partner_id',
            'partner_center_id',
            'field_agent_id',
            'review_status',
            'workflow_status',
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
            'last_comment',
            'status',
            'created_at',
            'updated_at',
        ])
        ->latest('id')
        ->paginate(
            min(max((int) $request->query('per_page', 15), 1), 100)
        );

    return response()->json([
        'success' => true,
        'message' => 'Event activities retrieved successfully.',

        'data' => $activities->items(),

        'pagination' => [
            'current_page' => $activities->currentPage(),
            'last_page' => $activities->lastPage(),
            'per_page' => $activities->perPage(),
            'total' => $activities->total(),
        ],

        'summary' => $summary,
    ]);
}


    /**
     * Show one activity, only if it belongs to the authenticated agent.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $agent = $request->user('field_agent');

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated Field Agent.',
            ], 401);
        }

        $activity = EventTransaction::query()
            ->where('field_agent_id', $agent->getAuthIdentifier())
            ->select([
                'id',
                'learner_id',
                'partner_id',
                'partner_center_id',
                'field_agent_id',
                'review_status',
                'workflow_status',
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
                'uploaded_doc_1',
                'uploaded_doc_2',
                'uploaded_doc_3',
                'last_comment',
                'uploaded_doc_links',
                'status',
                'created_at',
                'updated_at',
            ])
            ->find($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event activity retrieved successfully.',
            'data' => $activity,
        ]);
    }




        /**
         * Update editable details of an activity belonging to the
         * authenticated Field Agent.
         */
       public function update(Request $request, int $id): JsonResponse
        {
            $agent = $request->user('field_agent');

            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated Field Agent.',
                ], 401);
            }

            $activity = EventTransaction::query()
                ->where('field_agent_id', $agent->getAuthIdentifier())
                ->find($id);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activity not found.',
                ], 404);
            }

            if (!in_array(
                strtolower((string) $activity->workflow_status),
                ['draft', 'submitted'],
                true
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'This activity cannot be edited in its current workflow status.',
                ], 422);
            }

            $validated = $request->validate([
                'learner_id' => [
                    'required',
                    'integer',
                    'exists:learners,id',
                ],

                'event_master_id' => [
                    'required',
                    'integer',
                    'exists:event_master,id',
                ],

                'event_master_type_id' => [
                    'required',
                    'integer',
                    'exists:event_master_type,id',
                ],

                'beneficiary_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'beneficiary_phone_number' => [
                    'required',
                    'string',
                    'max:20',
                ],

                'comment' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'document_1' => [
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:10240',
                ],

                'document_2' => [
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:10240',
                ],

                'document_3' => [
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:10240',
                ],
            ]);

            // Validate the selected Event Type and Category combination.
            $eventMaster = EventMaster::query()
                ->with(['eventMasterType', 'eventCategory'])
                ->whereKey($validated['event_master_id'])
                ->where(
                    'event_master_type_id',
                    $validated['event_master_type_id']
                )
                ->where('status', true)
                ->first();

            if (
                !$eventMaster ||
                !$eventMaster->eventMasterType ||
                !$eventMaster->eventCategory ||
                !$eventMaster->eventMasterType->status ||
                !$eventMaster->eventCategory->status
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected Event Type and Category combination is invalid or inactive.',
                ], 422);
            }

            // Prepare the data for the centralized transaction service.
            $updateData = [
                'learner_id' => $validated['learner_id'],
                'event_master_id' => (string) $eventMaster->id,
                'event_master_type_id' => (string) $eventMaster->event_master_type_id,
                'event_type_name' => $eventMaster->eventMasterType->name,
                'event_category_name' => $eventMaster->eventCategory->name,
                'beneficiary_name' => $validated['beneficiary_name'],
                'beneficiary_phone_number' => $validated['beneficiary_phone_number'],
            ];

            // Preserve the existing comment and make it available to the audit log.
            if ($request->exists('comment')) {
                $updateData['last_comment'] = $validated['comment'] ?? null;
                $updateData['comment'] = $validated['comment'] ?? null;
            }

            // Store documents on the public disk.
            // Existing document paths remain unchanged if no replacement is uploaded.
            foreach ([1, 2, 3] as $number) {
                $field = 'document_' . $number;

                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store(
                        'uploads/eventtransaction',
                        'public'
                    );

                    if (!$path) {
                        return response()->json([
                            'success' => false,
                            'message' => "Document {$number} upload failed.",
                        ], 500);
                    }

                    $updateData['uploaded_doc_' . $number] = $path;
                }
            }

            // Centralized update + audit logging.
            // Workflow status is intentionally not changed by this edit endpoint.
            $activity = $this->service->update(
                $activity,
                $updateData,
                $agent,
                'fieldAgent'
            );

            return response()->json([
                'success' => true,
                'message' => 'Event activity updated successfully.',
                'data' => $activity->fresh(),
            ]);
        }







































}
