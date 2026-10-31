<?php

namespace App\Http\Controllers\Api\PlacementPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearnerApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $placementUser = $request->user('partner_placement');

        if (!$placementUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $placementUserId = $placementUser->id;

        /*
        |--------------------------------------------------------------------------
        | Validate Filters
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],

            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'agent' => ['nullable', 'string', 'max:255'],

            'name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'digital' => ['nullable', 'string', 'max:255'],
            'english' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $status = trim((string) ($validated['status'] ?? 'all'));
        $agent = trim((string) ($validated['agent'] ?? 'all'));

        $perPage = (int) ($validated['per_page'] ?? 10);

        /*
        |--------------------------------------------------------------------------
        | Assigned Field Agent CSC IDs
        |--------------------------------------------------------------------------
        | Only Field Agents associated with the logged-in Placement Partner.
        */

        $assignedCscIds = DB::table('field_agents')
            ->where('placement_partner_user_id', $placementUserId)
            ->whereNotNull('csc_id')
            ->where('csc_id', '<>', '')
            ->select('csc_id')
            ->distinct();

        /*
        |--------------------------------------------------------------------------
        | Base Learner Scope
        |--------------------------------------------------------------------------
        | learners.unit = assigned field_agents.csc_id
        |
        | Using a subquery avoids duplicate learner records when multiple
        | Field Agents have the same CSC ID.
        */

        $baseQuery = DB::table('learners as l')
            ->whereIn('l.unit', $assignedCscIds);

        /*
        |--------------------------------------------------------------------------
        | Placement Partner Summary
        |--------------------------------------------------------------------------
        */

        $total = (clone $baseQuery)->count();

        $monthStart = now()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();

        $thisMonth = (clone $baseQuery)
            ->where('l.created_at', '>=', $monthStart)
            ->where('l.created_at', '<', $nextMonthStart)
            ->count();

        $certificationDone = (clone $baseQuery)
            ->where('l.course_completed', 1)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Assigned Field Agent Dropdown
        |--------------------------------------------------------------------------
        */

        $agents = DB::table('field_agents')
            ->where('placement_partner_user_id', $placementUserId)
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Base Learners Listing
        |--------------------------------------------------------------------------
        | Use a correlated subquery to fetch the assigned Field Agent.
        | This prevents duplicate learner rows from a normal JOIN.
        */

        $query = (clone $baseQuery)
            ->select([
                'l.id',
                'l.first_name',
                'l.last_name',
                'l.email',
                'l.phone_number',
                'l.city',
                'l.state',
                'l.unit',
                'l.digital_proficiency_level',
                'l.english_proficiency_level',
                'l.source_created_at',
                'l.course_completed',
                'l.created_at',
                'l.status',
            ])

            ->selectSub(function ($subquery) use ($placementUserId) {
                $subquery
                    ->from('field_agents as fa')
                    ->select('fa.name')
                    ->whereColumn('fa.csc_id', 'l.unit')
                    ->where(
                        'fa.placement_partner_user_id',
                        $placementUserId
                    )
                    ->orderBy('fa.id')
                    ->limit(1);
            }, 'field_agent')

            ->selectSub(function ($subquery) use ($placementUserId) {
                $subquery
                    ->from('field_agents as fa')
                    ->select('fa.sakhi_id')
                    ->whereColumn('fa.csc_id', 'l.unit')
                    ->where(
                        'fa.placement_partner_user_id',
                        $placementUserId
                    )
                    ->orderBy('fa.id')
                    ->limit(1);
            }, 'sakhi_id');

        /*
        |--------------------------------------------------------------------------
        | Quick Search
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {
            $like = "%{$search}%";

            $query->where(function ($q) use ($like, $placementUserId) {

                $q->where('l.first_name', 'LIKE', $like)
                    ->orWhere('l.last_name', 'LIKE', $like)

                    ->orWhereRaw(
                        "CONCAT_WS(' ', l.first_name, l.last_name) LIKE ?",
                        [$like]
                    )

                    ->orWhereRaw(
                        "CONCAT_WS('-', CONCAT_WS(' ', l.first_name, l.last_name), l.phone_number) LIKE ?",
                        [$like]
                    )

                    ->orWhere('l.phone_number', 'LIKE', $like)
                    ->orWhere('l.email', 'LIKE', $like)
                    ->orWhere('l.city', 'LIKE', $like)
                    ->orWhere('l.state', 'LIKE', $like)
                    ->orWhere('l.unit', 'LIKE', $like)
                    ->orWhere('l.digital_proficiency_level', 'LIKE', $like)
                    ->orWhere('l.english_proficiency_level', 'LIKE', $like)

                    ->orWhereExists(function ($subquery) use (
                        $like,
                        $placementUserId
                    ) {
                        $subquery
                            ->selectRaw('1')
                            ->from('field_agents as fa')
                            ->whereColumn('fa.csc_id', 'l.unit')
                            ->where(
                                'fa.placement_partner_user_id',
                                $placementUserId
                            )
                            ->where(function ($agentQuery) use ($like) {
                                $agentQuery
                                    ->where('fa.name', 'LIKE', $like)
                                    ->orWhere('fa.sakhi_id', 'LIKE', $like);
                            });
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Advanced Search - Learner Name
        |--------------------------------------------------------------------------
        */

        $name = trim((string) ($validated['name'] ?? ''));

        if ($name !== '') {
            $like = "%{$name}%";

            $query->where(function ($q) use ($like) {
                $q->where('l.first_name', 'LIKE', $like)
                    ->orWhere('l.last_name', 'LIKE', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(' ', l.first_name, l.last_name) LIKE ?",
                        [$like]
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Advanced Search - Individual Fields
        |--------------------------------------------------------------------------
        */

        $advancedFilters = [
            'mobile' => 'l.phone_number',
            'email' => 'l.email',
            'unit' => 'l.unit',
            'state' => 'l.state',
            'city' => 'l.city',
            'digital' => 'l.digital_proficiency_level',
            'english' => 'l.english_proficiency_level',
        ];

        foreach ($advancedFilters as $parameter => $column) {
            $value = trim((string) ($validated[$parameter] ?? ''));

            if ($value !== '') {
                $query->where(
                    $column,
                    'LIKE',
                    "%{$value}%"
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Registration Status Filter
        |--------------------------------------------------------------------------
        */

        if ($status !== '' && $status !== 'all') {
            $query->where('l.status', $status);
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned Field Agent Filter
        |--------------------------------------------------------------------------
        */

        if ($agent !== '' && $agent !== 'all') {
            $query->whereExists(function ($subquery) use (
                $placementUserId,
                $agent
            ) {
                $subquery
                    ->selectRaw('1')
                    ->from('field_agents as fa')
                    ->whereColumn('fa.csc_id', 'l.unit')
                    ->where(
                        'fa.placement_partner_user_id',
                        $placementUserId
                    )
                    ->where('fa.name', $agent);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Skills Status
        |--------------------------------------------------------------------------
        */

        $query->selectRaw("
            CASE
                WHEN l.course_completed = 1 THEN 'accepted'
                ELSE 'not_started'
            END AS skills_status
        ");

        /*
        |--------------------------------------------------------------------------
        | Job Status
        |--------------------------------------------------------------------------
        | Latest transaction among event_master_type_id 1, 2, 5.
        */

        $query->selectSub(function ($subquery) {

            $subquery
                ->from('event_transactions as et')
                ->selectRaw("
                    CASE
                        WHEN LOWER(TRIM(et.review_status)) = 'accepted'
                            THEN 'accepted'

                        WHEN LOWER(TRIM(et.review_status)) = 'rejected'
                            THEN 'rejected'

                        WHEN LOWER(TRIM(et.review_status))
                            IN ('pending', 'under_review')
                            THEN 'pending'

                        WHEN LOWER(TRIM(et.review_status)) = 'open'
                            AND LOWER(TRIM(et.workflow_status)) = 'submitted'
                            THEN 'submitted'

                        ELSE 'not_started'
                    END
                ")
                ->whereColumn('et.learner_id', 'l.id')
                ->whereIn('et.event_master_type_id', [1, 2, 5])
                ->orderByDesc('et.id')
                ->limit(1);

        }, 'job_status');

        /*
        |--------------------------------------------------------------------------
        | Social Protection Status
        |--------------------------------------------------------------------------
        */

        $query->selectSub(function ($subquery) {

            $subquery
                ->from('event_transactions as et')
                ->selectRaw("
                    CASE
                        WHEN LOWER(TRIM(et.review_status)) = 'accepted'
                            THEN 'accepted'

                        WHEN LOWER(TRIM(et.review_status)) = 'rejected'
                            THEN 'rejected'

                        WHEN LOWER(TRIM(et.review_status))
                            IN ('pending', 'under_review')
                            THEN 'pending'

                        WHEN LOWER(TRIM(et.review_status)) = 'open'
                            AND LOWER(TRIM(et.workflow_status)) = 'submitted'
                            THEN 'submitted'

                        ELSE 'not_started'
                    END
                ")
                ->whereColumn('et.learner_id', 'l.id')
                ->where('et.event_master_type_id', 3)
                ->orderByDesc('et.id')
                ->limit(1);

        }, 'social_protection_status');

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $learners = $query
            ->orderByDesc('l.created_at')
            ->orderByDesc('l.id')
            ->paginate($perPage);

        /*
        |--------------------------------------------------------------------------
        | Normalize Missing Transaction Statuses
        |--------------------------------------------------------------------------
        */

        $learners->getCollection()->transform(function ($learner) {

            $learner->job_status =
                $learner->job_status ?? 'not_started';

            $learner->social_protection_status =
                $learner->social_protection_status ?? 'not_started';

            return $learner;
        });

        /*
        |--------------------------------------------------------------------------
        | Response - Same Structure as Partner API
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'summary' => [
                'total' => $total,
                'this_month' => $thisMonth,

                // Existing React compatibility:
                // This represents completed certifications.
                'field_agents' => $certificationDone,

                'certification_completed' => $certificationDone,
            ],

            'agents' => $agents,

            'data' => $learners->items(),

            'pagination' => [
                'current_page' => $learners->currentPage(),
                'last_page' => $learners->lastPage(),
                'per_page' => $learners->perPage(),
                'total' => $learners->total(),
            ],
        ]);
    }
}
