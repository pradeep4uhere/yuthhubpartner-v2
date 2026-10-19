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
use Illuminate\Support\Facades\Storage;
use App\Exports\EventActivitiesExport;
use App\Services\ExportScopeService;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ExportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use InvalidArgumentException;


class EventActivityController extends Controller
{

        protected EventTransactionService $service;

        /**
         * Create the event activity controller.
         *
         * @param ExportScopeService $exportScopeService
         */
        public function __construct(EventTransactionService $service,
            protected ExportScopeService $exportScopeService,
            protected ExportService $exportService
        )
        {
            $this->service = $service;
        }


/**
 * Search learners assigned to the authenticated field agent.
 *
 * Searches by first name, last name, full name, or phone number.
 * Only returns learners whose unit matches the authenticated
 * field agent's CSC ID.
 *
 * @param Request $request
 * @return JsonResponse
 */
public function searchLearners(Request $request): JsonResponse
{
    $agent = $request->user('field_agent');

    if (!$agent) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated Field Agent.',
        ], 401);
    }

    if (empty($agent->csc_id)) {
        return response()->json([
            'success' => false,
            'message' => 'Field Agent CSC ID not found.',
        ], 403);
    }

    $search = trim((string) $request->query('q', ''));

    if (mb_strlen($search) < 2) {
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }

        /*
        |--------------------------------------------------------------------------
        | Search Learners Assigned to This Field Agent
        |--------------------------------------------------------------------------
        | Restricts results to learners whose unit matches the agent's CSC ID.
        */
        $learners = DB::table('learners')
            ->select([
                'id',
                'first_name',
                'last_name',
                'phone_number',
            ])
            ->where('unit', $agent->csc_id)
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

                // Ignore placeholder last names.
                if (in_array(
                    strtoupper($lastName),
                    ['NA', 'N/A', 'NULL', '-'],
                    true
                )) {
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

    // Base query: only the logged-in Field Agent's activities.
    $baseQuery = EventTransaction::query()
        ->where('field_agent_id', $agent->getAuthIdentifier());

    // Apply filters to both listing and summary.
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

    // Summary across all matching records.
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

    // Load relationships to retrieve names and related details.
    $activities = (clone $baseQuery)
        ->with([
            'fieldType',
            'industryType',
            'eventMaster',
            'eventMasterType',
            'eventCategory',
            'latestLog',
        ])
        ->select([
            'id',
            'learner_id',
            'partner_id',
            'partner_center_id',
            'partner_placement_user_id',
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
        ->with([
            'logs' => function ($query) {
                $query->orderByDesc('created_at');
            },
            'logs.user',
        ])
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

            $workflowStatus = strtolower(trim(
                (string) $activity->workflow_status
            ));




            $editableStatuses = [
                'draft',
                'submitted',
                'resubmitted',
                'returned',
                'rejected',
                'under_review',
            ];

           if (!in_array($workflowStatus, $editableStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This activity cannot be edited in its current workflow status.',
                'workflow_status' => $activity->workflow_status,
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

                'workflow_status' => [
                        'sometimes',
                        'nullable',
                        'string',
                        'max:50',
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


            /*
            |--------------------------------------------------------------------------
            | Resolve Requested Workflow Status
            |--------------------------------------------------------------------------
            | Use the status submitted by the Field Agent when provided.
            | If the request omits the status, preserve the existing status.
            */
            $requestedWorkflowStatus = strtolower(trim(
                (string) (
                    $validated['workflow_status']
                    ?? $activity->workflow_status
                )
            ));

            $allowedWorkflowStatuses = [
                'draft',
                'submitted',
                'resubmitted',
                'returned',
                'rejected',
                'under_review',
            ];

            if (!in_array($requestedWorkflowStatus, $allowedWorkflowStatuses, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid workflow status.',
                    'workflow_status' => $validated['workflow_status'] ?? null,
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
                'workflow_status' => $requestedWorkflowStatus,
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

            $isResubmission = in_array(
                strtolower(trim((string) $activity->workflow_status)),
                ['returned', 'rejected'],
                true
            );

            /*
            |--------------------------------------------------------------------------
            | Centralized Update and Audit Logging
            |--------------------------------------------------------------------------
            | The service remains responsible for persisting the update and
            | recording its audit information.
            */
            $updateData['_is_resubmission'] = $isResubmission;
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




/**
 * Create a new event activity for the authenticated Field Agent.
 *
 * Validates the submitted learner and event details, verifies that the
 * selected event type and category are active, handles document uploads,
 * and creates the event transaction through EventTransactionService.
 *
 * The new activity is created with the default workflow and review statuses
 * configured by the service.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */

public function store(Request $request): JsonResponse
{
    $agent = $request->user('field_agent');

    if (!$agent) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated Field Agent.',
        ], 401);
    }

    $validated = $request->validate([
        'learner_id' => 'required|integer|exists:learners,id',
        'event_master_id' => 'required|integer|exists:event_master,id',
        'event_master_type_id' => 'required|integer|exists:event_master_type,id',
        'beneficiary_name' => 'required|string|max:255',
        'beneficiary_phone_number' => 'required|string|max:20',
        'comment' => 'nullable|string|max:5000',
        'monthly_income' => ['required', 'numeric', 'min:0'],
        'workflow_status' => ['required', 'in:DRAFT,SUBMITTED'],
        'document_1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        'document_2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        'document_3' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
    ]);

    $eventMaster = EventMaster::query()
        ->with(['eventMasterType', 'eventCategory'])
        ->whereKey($validated['event_master_id'])
        ->where('event_master_type_id', $validated['event_master_type_id'])
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
            'message' => 'Invalid or inactive event type/category combination.',
        ], 422);
    }

    $uploadedFiles = [];

    try {
        $data = [
            'learner_id' => $validated['learner_id'],
            'field_agent_id' => $agent->getAuthIdentifier(),
            // Partner mapping from the FieldAgent table.
            'partner_id' => $agent->partner_id,
            'partner_center_id' => $agent->partner_center_id,
            'partner_placement_user_id' => $agent->placement_partner_user_id,

            'event_master_id' => $eventMaster->id,
            'event_master_type_id' => $eventMaster->event_master_type_id,
            'event_type_name' => $eventMaster->eventMasterType->name,
            'event_category_name' => $eventMaster->eventCategory->name,
            'event_create_date' => now()->toDateString(),
            'beneficiary_name' => $validated['beneficiary_name'],
            'event_value' => $validated['monthly_income'] ?? 0,
            'beneficiary_phone_number' => $validated['beneficiary_phone_number'],
        ];
        $data['workflow_status'] = $validated['workflow_status'];

        if ($request->exists('comment')) {
            $data['last_comment'] = $validated['comment'] ?? null;
        }

        foreach ([1, 2, 3] as $number) {
            $field = "document_{$number}";

            if ($request->hasFile($field)) {
                $path = $request->file($field)->store(
                    'uploads/eventtransaction',
                    'public'
                );

                if (!$path) {
                    throw new \RuntimeException(
                        "Document {$number} upload failed."
                    );
                }

                $uploadedFiles[] = $path;
                $data["uploaded_doc_{$number}"] = $path;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Event Activity
        |--------------------------------------------------------------------------
        | A Field Agent cannot create the same event for the same learner more| than once for the same Event Master and Event Master Type combination.
        |--------------------------------------------------------------------------
        */
        $duplicateExists = \App\Models\EventTransaction::query()
            ->where('learner_id', $validated['learner_id'])
            ->where('field_agent_id', $agent->getAuthIdentifier())
            ->where('event_master_id', $eventMaster->id)
            ->where('event_master_type_id', $eventMaster->event_master_type_id)
            ->exists();

        if ($duplicateExists) {
            return response()->json([
                'success' => false,
                'message' => 'This event has already been recorded for the selected learner.',
                'errors' => [
                    'learner_id' => [
                        'This learner already has a record for this event and event type.',
                    ],
                ],
            ], 422);
        }



        $activity = $this->service->create(
            $data,
            $agent,
            'fieldAgent'
        );

        return response()->json([
            'success' => true,
            'message' => 'Event activity created successfully.',
            'data' => $activity->load([
                'learner',
                'eventMaster',
                'eventMasterType',
                'logs',
            ]),
        ], 201);

    } catch (\Throwable $e) {
        foreach ($uploadedFiles as $path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create event activity.',
        ], 500);
    }
}



    /**
     * Export all event activities belonging to the authenticated field agent.
     *
     * Applies the optional review status, workflow status, and search filters.
     * The export is not restricted by the activity listing pagination.
     *
     * @param Request $request The incoming export request and optional filters.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'review_status' => [
                'nullable',
                'string',
                'in:Open,Pending,Accepted,Rejected,Return',
            ],
            'workflow_status' => [
                'nullable',
                'string',
                'in:draft,submitted',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Resolve Authenticated Field Agent
        |--------------------------------------------------------------------------
        | Restricts the export to the current agent's own records.
        |--------------------------------------------------------------------------
        */
        $agent = $request->user();

        abort_unless($agent, 401, 'Unauthenticated.');

        /*
        |--------------------------------------------------------------------------
        | Build Authorized Export Query
        |--------------------------------------------------------------------------
        | Apply the centralized requester scope before applying optional filters.
        |--------------------------------------------------------------------------
        */
        $agent = $request->user();

        abort_unless($agent instanceof \Illuminate\Database\Eloquent\Model, 401);

        $query = $this->exportScopeService
            ->eventActivityQuery($agent)
            ->with([
                'eventMasterType',
                'eventMaster',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Centralized Export Request
            |--------------------------------------------------------------------------
            | ExportService saves the export record and dispatches ProcessExportJob.
            |--------------------------------------------------------------------------
            */
            try {
                $export = $this->exportService->create($agent, [
                    'type' => 'events',
                    'scope_type' => 'field_agent',
                    'scope_id' => $agent->getKey(),
                    'title' => 'Event Transactions Export',
                    'filters' => array_filter(
                        $validated,
                        static fn ($value) => $value !== null && $value !== ''
                    ),
                ]);
            } catch (InvalidArgumentException $exception) {
                report($exception);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create the export request.',
                ], 422);
            }
        /*
        |--------------------------------------------------------------------------
        | Download Excel Workbook
        |--------------------------------------------------------------------------
        */
        $fileName = 'event-activities-' .time().'-'. now()->format('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(
            new EventActivitiesExport($query),
            $fileName
        );
    }
































}
