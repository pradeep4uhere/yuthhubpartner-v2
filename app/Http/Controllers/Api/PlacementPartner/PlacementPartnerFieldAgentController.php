<?php

namespace App\Http\Controllers\Api\PlacementPartner;

use App\Http\Controllers\Controller;
use App\Models\EventTransaction;
use App\Models\FieldAgent;
use App\Models\PartnerPlacementUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Exports\PlacementPartnerFieldAgentTransactionsExport;
use Maatwebsite\Excel\Facades\Excel;

class PlacementPartnerFieldAgentController extends Controller
{
    private const JOB_TYPES = ['1', '2', '5'];
    private const SOCIAL_TYPES = ['3'];

    private const COUNTABLE_WORKFLOWS = [
        'submitted',
        'under_review',
        'approved',
        'rejected',
    ];

    private function authenticatedPlacementPartner(
        Request $request
    ): ?PartnerPlacementUser {
        $partner = $request->user('partner_placement');

        if (!$partner || $partner->status !== 'active') {
            return null;
        }

        return $partner;
    }

    /**
     * Restrict all agent queries to this Placement Partner.
     */
    private function agentQuery(int $placementPartnerId): Builder
    {
        return FieldAgent::query()
            ->where(
                'field_agents.placement_partner_user_id',
                $placementPartnerId
            );
    }

    private function applyFilters(
        Builder $query,
        array $filters
    ): Builder {
        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('field_agents.name', 'like', "%{$search}%")
                    ->orWhere('field_agents.csc_id', 'like', "%{$search}%")
                    ->orWhere('field_agents.sakhi_id', 'like', "%{$search}%")
                    ->orWhere(
                        'field_agents.contact_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'field_agents.email',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $query->when(
            $filters['state'] ?? null,
            fn ($q, $value) => $q->where(
                'field_agents.state',
                $value
            )
        );

        $query->when(
            $filters['district'] ?? null,
            fn ($q, $value) => $q->where(
                'field_agents.district',
                $value
            )
        );

        $query->when(
            $filters['status'] ?? null,
            fn ($q, $value) => $q->where(
                'field_agents.status',
                $value
            )
        );

        return $query;
    }

    /**
     * Aggregate transaction counts for assigned agents only.
     *
     * Counts: Total, Submitted, Action Required, Accepted, Rejected.
     */
    private function transactionCounts(int $placementPartnerId)
    {
        $agentIds = $this->agentQuery($placementPartnerId)
            ->select('field_agents.id');

        $query = DB::table('event_transactions as et')
            ->whereIn('et.field_agent_id', $agentIds)
            ->whereNotNull('et.field_agent_id')
            ->whereIn('et.workflow_status', self::COUNTABLE_WORKFLOWS)
            ->whereIn(
                'et.event_master_type_id',
                array_merge(self::JOB_TYPES, self::SOCIAL_TYPES)
            )
            ->select('et.field_agent_id')
            ->groupBy('et.field_agent_id');

        foreach ([
            'jobs' => self::JOB_TYPES,
            'social' => self::SOCIAL_TYPES,
        ] as $prefix => $types) {
            $placeholders = implode(
                ',',
                array_fill(0, count($types), '?')
            );

            $typeCondition =
                "et.event_master_type_id IN ($placeholders)";

            $query->selectRaw(
                "SUM(CASE WHEN $typeCondition THEN 1 ELSE 0 END) AS {$prefix}_total",
                $types
            );

            $statuses = [
                'submitted' => ['workflow_status', 'submitted'],
                'required' => ['review_status', 'action_required'],
                'accepted' => ['review_status', 'accepted'],
                'rejected' => ['review_status', 'rejected'],
            ];

            foreach ($statuses as $alias => [$column, $value]) {
                $query->selectRaw(
                    "SUM(
                        CASE
                            WHEN $typeCondition AND et.{$column} = ?
                            THEN 1 ELSE 0
                        END
                    ) AS {$prefix}_{$alias}",
                    [...$types, $value]
                );
            }
        }

        return $query;
    }

    private function withTransactionCounts(
        Builder $query,
        int $placementPartnerId
    ): Builder {
        return $query->leftJoinSub(
            $this->transactionCounts($placementPartnerId),
            'tc',
            fn ($join) => $join->on(
                'tc.field_agent_id',
                '=',
                'field_agents.id'
            )
        );
    }

    /**
     * GET /api/partner-placement/field-agents
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $this->authenticatedPlacementPartner($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Placement Partner.',
            ], 403);
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => [
                'nullable',
                'integer',
                Rule::in([10, 20, 50]),
            ],
        ]);

        $baseQuery = $this->applyFilters(
            $this->agentQuery($partner->id),
            $filters
        );

        $summary = $this->withTransactionCounts(
            clone $baseQuery,
            $partner->id
        )
            ->selectRaw('COUNT(field_agents.id) AS total_agents')
            ->selectRaw(
                'COALESCE(SUM(tc.jobs_total), 0) AS total_jobs'
            )
            ->selectRaw(
                'COALESCE(SUM(tc.jobs_accepted), 0)
                 + COALESCE(SUM(tc.social_accepted), 0)
                 AS total_accepted'
            )
            ->selectRaw(
                'COALESCE(SUM(tc.jobs_required), 0)
                 + COALESCE(SUM(tc.social_required), 0)
                 AS action_required'
            )
            ->first();

        $agents = $this->withTransactionCounts(
            clone $baseQuery,
            $partner->id
        )
            ->select('field_agents.*')
            ->addSelect([
                'tc.jobs_total',
                'tc.jobs_submitted',
                'tc.jobs_required',
                'tc.jobs_accepted',
                'tc.jobs_rejected',
                'tc.social_total',
                'tc.social_submitted',
                'tc.social_required',
                'tc.social_accepted',
                'tc.social_rejected',
            ])
            ->orderByDesc('field_agents.id')
            ->paginate($filters['per_page'] ?? 10);

        $agents->getCollection()->transform(function ($agent) {
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'agentId' => $agent->csc_id ?: $agent->sakhi_id,
                'mobile' => $agent->contact_number,
                'email' => $agent->email,

                // Placement Partner agents don't need a Partner Center join.
                'partner' => null,
                'center' => null,
                'partner_id' => null,
                'center_id' => null,

                'state' => $agent->state,
                'district' => $agent->district,
                'status' => $agent->status,

                'jobs' => [
                    (int) ($agent->jobs_total ?? 0),
                    (int) ($agent->jobs_submitted ?? 0),
                    (int) ($agent->jobs_required ?? 0),
                    (int) ($agent->jobs_accepted ?? 0),
                    (int) ($agent->jobs_rejected ?? 0),
                ],

                'social' => [
                    (int) ($agent->social_total ?? 0),
                    (int) ($agent->social_submitted ?? 0),
                    (int) ($agent->social_required ?? 0),
                    (int) ($agent->social_accepted ?? 0),
                    (int) ($agent->social_rejected ?? 0),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_agents' => (int) ($summary->total_agents ?? 0),
                    'total_jobs' => (int) ($summary->total_jobs ?? 0),
                    'total_accepted' => (int) ($summary->total_accepted ?? 0),
                    'action_required' => (int) ($summary->action_required ?? 0),
                ],
                'agents' => $agents,
            ],
        ]);
    }

    /**
     * GET /api/partner-placement/field-agents/filters
     */
    public function filters(Request $request): JsonResponse
    {
        $partner = $this->authenticatedPlacementPartner($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Placement Partner.',
            ], 403);
        }

        $filters = $request->validate([
            'state' => ['nullable', 'string', 'max:255'],
        ]);

        $states = $this->agentQuery($partner->id)
            ->whereNotNull('field_agents.state')
            ->where('field_agents.state', '<>', '')
            ->distinct()
            ->orderBy('field_agents.state')
            ->pluck('field_agents.state');

        $districtQuery = $this->agentQuery($partner->id);

        $districtQuery->when(
            $filters['state'] ?? null,
            fn ($q, $value) => $q->where(
                'field_agents.state',
                $value
            )
        );

        $districts = $districtQuery
            ->whereNotNull('field_agents.district')
            ->where('field_agents.district', '<>', '')
            ->distinct()
            ->orderBy('field_agents.district')
            ->pluck('field_agents.district');

        return response()->json([
            'success' => true,
            'data' => [
                'partners' => [[
                    'id' => $partner->id,
                    'name' => $partner->name,
                ]],
                'states' => $states,
                'districts' => $districts,
                'centers' => [],
            ],
        ]);
    }

    /**
     * GET /api/partner-placement/field-agents/{id}
     *
     * Field Agent details and their event transactions.
     */
    public function fieldAgentDetails(
        Request $request,
        int $id
    ): JsonResponse {
        $partner = $this->authenticatedPlacementPartner($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Placement Partner.',
            ], 403);
        }

        $agent = $this->agentQuery($partner->id)
            ->whereKey($id)
            ->first();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Field Agent not found.',
            ], 404);
        }

        $filters = $request->validate([
                'search' => ['nullable', 'string', 'max:255'],

                'status' => [
                    'nullable',
                    Rule::in([
                        'submitted',
                        'action_required',
                        'accepted',
                        'rejected',
                    ]),
                ],

                // Advanced Search filters.
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],

                'function_type' => ['nullable', 'string', 'max:255'],
                'industry_type' => ['nullable', 'string', 'max:255'],
                'state' => ['nullable', 'string', 'max:255'],
                'district' => ['nullable', 'string', 'max:255'],

                'per_page' => [
                    'nullable',
                    'integer',
                    Rule::in([10, 25, 50, 100]),
                ],

                'page' => ['nullable', 'integer', 'min:1'],
            ]);

        // Do not filter by partner_id: assigned agent ownership is the scope.
        $base = EventTransaction::query()
            ->where('field_agent_id', $agent->id)
            ->whereIn(
                'workflow_status',
                self::COUNTABLE_WORKFLOWS
            );

        $summary = (clone $base)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw(
                "SUM(CASE WHEN workflow_status = 'submitted' THEN 1 ELSE 0 END) AS submitted"
            )
            ->selectRaw(
                "SUM(CASE WHEN review_status = 'action_required' THEN 1 ELSE 0 END) AS action_required"
            )
            ->selectRaw(
                "SUM(CASE WHEN review_status = 'accepted' THEN 1 ELSE 0 END) AS accepted"
            )
            ->selectRaw(
                "SUM(CASE WHEN review_status = 'rejected' THEN 1 ELSE 0 END) AS rejected"
            )
            ->first();

        $query = clone $base;
        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('event_master_id', 'like', "%{$search}%")
                    ->orWhere('event_type_name', 'like', "%{$search}%")
                    ->orWhere('event_category_name', 'like', "%{$search}%")
                    ->orWhere('beneficiary_name', 'like', "%{$search}%")
                    ->orWhere(
                        'beneficiary_phone_number',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'submitted') {
                $query->where('workflow_status', 'submitted');
            } else {
                $query->where('review_status', $filters['status']);
            }
        }



        // Filter by Event Transaction submission date.
        if (!empty($filters['date_from'])) {
            $query->whereDate(
                'event_date_submitted',
                '>=',
                $filters['date_from']
            );
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate(
                'event_date_submitted',
                '<=',
                $filters['date_to']
            );
        }

        // Filter by Function Type relation.
        if (!empty($filters['function_type'])) {
            $query->whereHas('fieldType', function ($q) use ($filters) {
                $q->where(
                    'name',
                    'like',
                    '%' . $filters['function_type'] . '%'
                );
            });
        }

        // Filter by Industry Type relation.
        if (!empty($filters['industry_type'])) {
            $query->whereHas('industryType', function ($q) use ($filters) {
                $q->where(
                    'name',
                    'like',
                    '%' . $filters['industry_type'] . '%'
                );
            });
        }

        // Filter State and District through the associated Learner.
        if (!empty($filters['state'])) {
            $query->whereHas('learner', function ($q) use ($filters) {
                $q->where('state', 'like', '%' . $filters['state'] . '%');
            });
        }

        if (!empty($filters['district'])) {
            $query->whereHas('learner', function ($q) use ($filters) {
                $q->where('district', 'like', '%' . $filters['district'] . '%');
            });
        }


        $transactions = $query
            ->with(['learner', 'fieldType', 'industryType'])
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25);

        $transactions->getCollection()->transform(
            function ($tx) use ($agent) {
                $learner = $tx->learner;
                $profile = fn (string $key) => $learner?->getAttribute($key);

                $documents = [];

                foreach (
                    ['uploaded_doc_1', 'uploaded_doc_2', 'uploaded_doc_3']
                    as $i => $column
                ) {
                    $path = $tx->{$column};

                    if ($path) {
                        $exists = Storage::disk('public')->exists($path);

                        $documents[] = [
                            'label' => 'Document ' . ($i + 1),
                            'url' => $exists
                                ? Storage::disk('public')->url($path)
                                : null,
                        ];
                    }
                }

                return [
                    'id' => $tx->id,
                    'agent_id' => $agent->sakhi_id,
                    'unit_institute' => $agent->csc_id,
                    'event_name' => $tx->event_type_name,
                    'event_category' => $tx->event_category_name,
                    'beneficiary_name' => $tx->beneficiary_name,
                    'beneficiary_number' => $tx->beneficiary_phone_number,
                    'event_status' => $tx->review_status ?: $tx->workflow_status,
                    'event_submitted' => $tx->event_date_submitted
                        ? (string) $tx->event_date_submitted
                        : null,
                    'documents' => $documents,
                    'differently_abled' => $profile('differently_abled'),
                    'education_level' => $profile('education_level'),
                    'course_name' => $profile('course_name'),
                    'marital_status' => $profile('marital_status'),
                    'religion' => $profile('religion'),
                    'digital_proficiency_level' => $profile('digital_proficiency_level'),
                    'english_proficiency_level' => $profile('english_proficiency_level'),
                    'interested_opportunities' => $profile('interested_opportunities'),
                    'function_type' => $tx->fieldType?->name,
                    'industry_type' => $tx->industryType?->name,
                    'age_group' => $profile('age_group'),
                    'state' => $profile('state') ?: $agent->state,
                    'district' => $profile('district') ?: $agent->district,
                    'comment' => $tx->last_comment,
                    'updated' => $tx->updated_at?->toIso8601String(),
                ];
            }
        );

        return response()->json([
            'success' => true,
            'data' => [
                'agent' => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'agentId' => $agent->csc_id ?: $agent->sakhi_id,
                    'mobile' => $agent->contact_number,
                    'center' => null,
                ],
                'summary' => [
                    'total' => (int) ($summary->total ?? 0),
                    'submitted' => (int) ($summary->submitted ?? 0),
                    'action_required' => (int) ($summary->action_required ?? 0),
                    'accepted' => (int) ($summary->accepted ?? 0),
                    'rejected' => (int) ($summary->rejected ?? 0),
                ],
                'transactions' => $transactions,
            ],
        ]);
    }



    public function export(\Illuminate\Http\Request $request)
{
    $placementPartner = $request->user('partner_placement');

    if (!$placementPartner) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    $filters = $request->input('filters', []);

    if (!is_array($filters)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid filters.',
        ], 422);
    }

    $agentIds = \Illuminate\Support\Facades\DB::table('field_agents')
        ->where('placement_partner_user_id', $placementPartner->id)
        ->pluck('id');

    if ($agentIds->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No associated field agents found to export.',
        ], 422);
    }

    try {
        $export = app(\App\Services\ExportService::class)->create(
            $placementPartner,
            [
                'type' => 'field_agents',
                'scope_type' => 'placement_partner',
                'scope_id' => $placementPartner->id,
                'title' => 'Placement Partner Field Agents',
                'filters' => $filters,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Export request submitted successfully.',
            'data' => $export,
        ], 202);

    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Unable to start field agent export. Check the export service configuration.',
        ], 500);
    }
}





        /**
         * Export all filtered transactions for a specific Field Agent as an Excel file.
         */
        public function exportFieldAgentTransactions(
            Request $request,
            int $id
        ) {
            $partner = $this->authenticatedPlacementPartner($request);

            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized Placement Partner.',
                ], 403);
            }

            // Verify that the requested Field Agent belongs to this Placement Partner.
            $agent = $this->agentQuery($partner->id)
                ->whereKey($id)
                ->first();

            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Field Agent not found.',
                ], 404);
            }

            // Validate the same filters supported by the transaction listing API.
            $filters = $request->validate([
                'search' => ['nullable', 'string', 'max:255'],
                'status' => [
                    'nullable',
                    Rule::in([
                        'submitted',
                        'action_required',
                        'accepted',
                        'rejected',
                    ]),
                ],
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
                'function_type' => ['nullable', 'string', 'max:255'],
                'industry_type' => ['nullable', 'string', 'max:255'],
                'state' => ['nullable', 'string', 'max:255'],
                'district' => ['nullable', 'string', 'max:255'],
            ]);

            // Restrict export to the selected agent and the same countable workflows.
            $query = EventTransaction::query()
                ->where('field_agent_id', $agent->id)
                ->whereIn('workflow_status', self::COUNTABLE_WORKFLOWS);

            // Apply the same free-text search as the listing API.
            $search = trim($filters['search'] ?? '');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('event_master_id', 'like', "%{$search}%")
                        ->orWhere('event_type_name', 'like', "%{$search}%")
                        ->orWhere('event_category_name', 'like', "%{$search}%")
                        ->orWhere('beneficiary_name', 'like', "%{$search}%")
                        ->orWhere(
                            'beneficiary_phone_number',
                            'like',
                            "%{$search}%"
                        );
                });
            }

            // Apply the selected workflow or review status.
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'submitted') {
                    $query->where('workflow_status', 'submitted');
                } else {
                    $query->where('review_status', $filters['status']);
                }
            }

            // Filter by transaction submission date.
            if (!empty($filters['date_from'])) {
                $query->whereDate(
                    'event_date_submitted',
                    '>=',
                    $filters['date_from']
                );
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate(
                    'event_date_submitted',
                    '<=',
                    $filters['date_to']
                );
            }

            // Filter transactions by Function Type.
            if (!empty($filters['function_type'])) {
                $query->whereHas('fieldType', function ($q) use ($filters) {
                    $q->where(
                        'name',
                        'like',
                        '%' . $filters['function_type'] . '%'
                    );
                });
            }

            // Filter transactions by Industry Type.
            if (!empty($filters['industry_type'])) {
                $query->whereHas('industryType', function ($q) use ($filters) {
                    $q->where(
                        'name',
                        'like',
                        '%' . $filters['industry_type'] . '%'
                    );
                });
            }

            // Filter by the associated learner's State.
            if (!empty($filters['state'])) {
                $query->whereHas('learner', function ($q) use ($filters) {
                    $q->where('state', 'like', '%' . $filters['state'] . '%');
                });
            }

            // Filter by the associated learner's District.
            if (!empty($filters['district'])) {
                $query->whereHas('learner', function ($q) use ($filters) {
                    $q->where('district', 'like', '%' . $filters['district'] . '%');
                });
            }

            // Return the backend-generated Excel file for all matching transactions.
            $safeAgentName = preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $agent->name ?: 'FieldAgent'
            );

            $fileName = 'FieldAgent_Transactions_' . $safeAgentName . '_' .
                now()->format('Ymd_His') . '.xlsx';

            return Excel::download(
                new PlacementPartnerFieldAgentTransactionsExport(
                    $query->with('fieldAgent')
                ),
                $fileName
            );
        }





}
