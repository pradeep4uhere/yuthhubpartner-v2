<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\EventTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\FieldAgent;
use App\Models\Partner;
use App\Models\PartnerCenter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class PartnerFieldAgentController extends Controller
{


   /*
    |--------------------------------------------------------------------------
    | Transaction Classification
    |--------------------------------------------------------------------------
    */

    private const JOB_TYPES = ['1', '2', '5'];

    private const SOCIAL_TYPES = ['3'];

    private const COUNTABLE_WORKFLOWS = [
        'submitted',
        'under_review',
        'approved',
        'rejected',
    ];

    /*
    |--------------------------------------------------------------------------
    | Authenticate Partner
    |--------------------------------------------------------------------------
    */

    private function authenticatedPartner(Request $request): ?Partner
    {
        $partner = $request->user('partner');

        if (!$partner || $partner->status !== 'active') {
            return null;
        }

        return $partner;
    }

    /*
    |--------------------------------------------------------------------------
    | Base Field Agent Query
    |--------------------------------------------------------------------------
    */

    private function agentQuery(int $partnerId): Builder
    {
        return FieldAgent::query()
            ->where('field_agents.partner_id', $partnerId);
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Search And Filters
    |--------------------------------------------------------------------------
    */

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
            fn ($q, $value) =>
                $q->where('field_agents.state', $value)
        );

        $query->when(
            $filters['district'] ?? null,
            fn ($q, $value) =>
                $q->where('field_agents.district', $value)
        );

        $query->when(
            $filters['center_id'] ?? null,
            fn ($q, $value) =>
                $q->where('field_agents.partner_center_id', $value)
        );

        $query->when(
            $filters['status'] ?? null,
            fn ($q, $value) =>
                $q->where('field_agents.status', $value)
        );

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Aggregated Transaction Counts
    |--------------------------------------------------------------------------
    | One grouped query for all agents belonging to the partner.
    |
    | Array order:
    | Total, Submitted, Action Required, Accepted, Rejected
    |--------------------------------------------------------------------------
    */

    private function transactionCounts(int $partnerId)
    {
        $query = DB::table('event_transactions as et')
            ->where('et.partner_id', $partnerId)
            ->whereNotNull('et.field_agent_id')
            ->whereIn(
                'et.workflow_status',
                self::COUNTABLE_WORKFLOWS
            )
            ->whereIn(
                'et.event_master_type_id',
                array_merge(
                    self::JOB_TYPES,
                    self::SOCIAL_TYPES
                )
            )
            ->select('et.field_agent_id')
            ->groupBy('et.field_agent_id');

        /*
        |--------------------------------------------------------------------------
        | Conditional Aggregation
        |--------------------------------------------------------------------------
        */

        $categories = [
            'jobs' => self::JOB_TYPES,
            'social' => self::SOCIAL_TYPES,
        ];

        foreach ($categories as $prefix => $types) {

            $placeholders = implode(
                ',',
                array_fill(0, count($types), '?')
            );

            $typeCondition =
                "et.event_master_type_id IN ($placeholders)";

            $query->selectRaw(
                "
                SUM(
                    CASE WHEN $typeCondition
                    THEN 1 ELSE 0 END
                ) AS {$prefix}_total
                ",
                $types
            );

            $statuses = [
                'submitted' => [
                    'workflow_status',
                    'submitted',
                ],
                'required' => [
                    'review_status',
                    'action_required',
                ],
                'accepted' => [
                    'review_status',
                    'accepted',
                ],
                'rejected' => [
                    'review_status',
                    'rejected',
                ],
            ];

            foreach ($statuses as $alias => [$column, $value]) {

                $query->selectRaw(
                    "
                    SUM(
                        CASE
                            WHEN $typeCondition
                            AND et.{$column} = ?
                            THEN 1
                            ELSE 0
                        END
                    ) AS {$prefix}_{$alias}
                    ",
                    [...$types, $value]
                );
            }
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Join Transaction Summary
    |--------------------------------------------------------------------------
    */

    private function withTransactionCounts(
        Builder $query,
        int $partnerId
    ): Builder {

        return $query->leftJoinSub(
            $this->transactionCounts($partnerId),
            'tc',
            function ($join) {

                $join->on(
                    'tc.field_agent_id',
                    '=',
                    'field_agents.id'
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Field Agent Listing
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $partner = $this->authenticatedPartner($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized partner.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],

            'center_id' => [
                'nullable',
                'integer',
                Rule::exists('partner_centers', 'id')
                    ->where('partner_id', $partner->id),
            ],

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

        /*
        |--------------------------------------------------------------------------
        | Filtered Base Query
        |--------------------------------------------------------------------------
        */

        $baseQuery = $this->applyFilters(
            $this->agentQuery($partner->id),
            $filters
        );

        /*
        |--------------------------------------------------------------------------
        | Dashboard Summary
        |--------------------------------------------------------------------------
        | Calculated across ALL matching agents, not just current page.
        |--------------------------------------------------------------------------
        */

        $summaryQuery = $this->withTransactionCounts(
            clone $baseQuery,
            $partner->id
        );

        $summary = $summaryQuery
            ->selectRaw('COUNT(field_agents.id) AS total_agents')
            ->selectRaw(
                'COALESCE(SUM(tc.jobs_total), 0) AS total_jobs'
            )
            ->selectRaw(
                '
                COALESCE(SUM(tc.jobs_accepted), 0)
                +
                COALESCE(SUM(tc.social_accepted), 0)
                AS total_accepted
                '
            )
            ->selectRaw(
                '
                COALESCE(SUM(tc.jobs_required), 0)
                +
                COALESCE(SUM(tc.social_required), 0)
                AS action_required
                '
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Paginated Listing
        |--------------------------------------------------------------------------
        */

        $query = $this->withTransactionCounts(
            clone $baseQuery,
            $partner->id
        );

        $agents = $query
            ->with([
                'partner',
                'partnerCenter',
            ])
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

        /*
        |--------------------------------------------------------------------------
        | Format React Response
        |--------------------------------------------------------------------------
        */

        $agents->getCollection()->transform(
            function ($agent) {

                return [
                    'id' => $agent->id,

                    'name' => $agent->name,

                    'agentId' => $agent->csc_id
                        ?: $agent->sakhi_id,

                    'mobile' => $agent->contact_number,

                    'email' => $agent->email,

                    'partner' => $agent->partner?->name,

                    'center' => $agent->partnerCenter?->name,

                    'partner_id' => $agent->partner_id,

                    'center_id' => $agent->partner_center_id,

                    'state' => $agent->state,

                    'district' => $agent->district,

                    'status' => $agent->status,

                    /*
                    | Total Jobs / Events
                    */

                    'jobs' => [
                        (int) ($agent->jobs_total ?? 0),
                        (int) ($agent->jobs_submitted ?? 0),
                        (int) ($agent->jobs_required ?? 0),
                        (int) ($agent->jobs_accepted ?? 0),
                        (int) ($agent->jobs_rejected ?? 0),
                    ],

                    /*
                    | Social Protection
                    */

                    'social' => [
                        (int) ($agent->social_total ?? 0),
                        (int) ($agent->social_submitted ?? 0),
                        (int) ($agent->social_required ?? 0),
                        (int) ($agent->social_accepted ?? 0),
                        (int) ($agent->social_rejected ?? 0),
                    ],
                ];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | API Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => [
                'summary' => [
                    'total_agents' => (int) $summary->total_agents,
                    'total_jobs' => (int) $summary->total_jobs,
                    'total_accepted' => (int) $summary->total_accepted,
                    'action_required' => (int) $summary->action_required,
                ],

                'agents' => $agents,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic Filter Options
    |--------------------------------------------------------------------------
    */

    public function filters(Request $request): JsonResponse
    {
        $partner = $this->authenticatedPartner($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized partner.',
            ], 403);
        }

        $filters = $request->validate([
            'state' => ['nullable', 'string', 'max:255'],
            'partner_id' => ['nullable', 'integer'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | States
        |--------------------------------------------------------------------------
        */

        $states = $this->agentQuery($partner->id)
            ->whereNotNull('state')
            ->where('state', '<>', '')
            ->distinct()
            ->orderBy('state')
            ->pluck('state');

        /*
        |--------------------------------------------------------------------------
        | Districts
        |--------------------------------------------------------------------------
        */

        $districtQuery = $this->agentQuery($partner->id);

        $districtQuery->when(
            $filters['state'] ?? null,
            fn ($q, $value) => $q->where('state', $value)
        );

        $districts = $districtQuery
            ->whereNotNull('district')
            ->where('district', '<>', '')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');

        /*
        |--------------------------------------------------------------------------
        | Partner Centers
        |--------------------------------------------------------------------------
        */

        $centers = PartnerCenter::query()
            ->where('partner_id', $partner->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        /*
        |--------------------------------------------------------------------------
        | Current Partner
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => [
                'partners' => [
                    [
                        'id' => $partner->id,
                        'name' => $partner->name,
                    ],
                ],

                'states' => $states,

                'districts' => $districts,

                'centers' => $centers,
            ],
        ]);
    }




    /**
     * Field Agent Details
     */
    public function fieldAgentDetails(Request $request, int $id): JsonResponse
    {
        $partner = $request->user('partner');
        if (!$partner || $partner->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Unauthorized partner.'], 403);
        }

        $agent = FieldAgent::query()
            ->with('partnerCenter')
            ->where('partner_id', $partner->id)
            ->findOrFail($id);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['submitted', 'action_required', 'accepted', 'rejected'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $base = EventTransaction::query()
            ->where('partner_id', $partner->id)
            ->where('field_agent_id', $agent->id)
            ->whereIn('workflow_status', ['submitted', 'under_review', 'approved', 'rejected']);

        $summary = (clone $base)->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN workflow_status = 'submitted' THEN 1 ELSE 0 END) as submitted")
            ->selectRaw("SUM(CASE WHEN review_status = 'action_required' THEN 1 ELSE 0 END) as action_required")
            ->selectRaw("SUM(CASE WHEN review_status = 'accepted' THEN 1 ELSE 0 END) as accepted")
            ->selectRaw("SUM(CASE WHEN review_status = 'rejected' THEN 1 ELSE 0 END) as rejected")
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
                    ->orWhere('beneficiary_phone_number', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'submitted') {
                $query->where('workflow_status', 'submitted');
            } else {
                $query->where('review_status', $filters['status']);
            }
        }

        $transactions = $query->with(['learner', 'fieldType', 'industryType'])
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25);

        // Learner profile attribute names below are provisional. Update these keys
        // to the actual learners table schema before relying on profile values.
        $transactions->getCollection()->transform(function ($tx) use ($agent) {
            $learner = $tx->learner;
            $profile = fn (string $key) => $learner?->getAttribute($key);
            $documents = [];
            foreach (['uploaded_doc_1', 'uploaded_doc_2', 'uploaded_doc_3'] as $i => $column) {
                $path = $tx->{$column};
                if ($path) {
                    // Use an authenticated download route instead if documents are private.
                    $documents[] = ['label' => 'Document ' . ($i + 1), 'url' => Storage::disk('public')->url($path)];
                }
            }
            return [
                'id' => $tx->id,
                'agent_id' => $agent->csc_id ?: $agent->sakhi_id,
                'unit_institute' => $profile('unit'),
                'event_name' => $tx->event_type_name,
                'event_category' => $tx->event_category_name,
                'beneficiary_name' => $tx->beneficiary_name,
                'beneficiary_number' => $tx->beneficiary_phone_number,
                'event_status' => $tx->review_status ?: $tx->workflow_status,
                'event_submitted' => $tx->event_date_submitted ? (string) $tx->event_date_submitted : null,
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
        });

        return response()->json([
            'success' => true,
            'data' => [
                'agent' => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'agentId' => $agent->csc_id ?: $agent->sakhi_id,
                    'mobile' => $agent->contact_number,
                    'center' => $agent->partnerCenter?->name,
                ],
                'summary' => [
                    'total' => (int) $summary->total,
                    'submitted' => (int) $summary->submitted,
                    'action_required' => (int) $summary->action_required,
                    'accepted' => (int) $summary->accepted,
                    'rejected' => (int) $summary->rejected,
                ],
                'transactions' => $transactions,
            ],
        ]);
    }



}
