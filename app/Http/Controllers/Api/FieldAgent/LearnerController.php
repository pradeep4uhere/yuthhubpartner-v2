<?php

namespace App\Http\Controllers\Api\FieldAgent;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearnerController extends Controller
{

/**
 * Retrieve a paginated list of active learners assigned to the
 * authenticated field agent, including their activity statuses.
 *
 * Skills status is determined by learners.course_completed.
 * Job status is determined by the latest transaction belonging to
 * event master types 1 or 5.
 * Social Protection status is determined by the latest transaction
 * belonging to event master type 3.
 *
 * Transaction statuses are mapped to frontend dot colors:
 * - Open + submitted: blue
 * - Rejected: red
 * - Pending or under_review: orange
 * - Accepted: green
 * - No matching activity or unrecognized status: gray
 *
 * Supports optional search by learner name or normalized mobile
 * number, as well as pagination. This endpoint is read-only.
 *
 * @param Request $request
 * @return JsonResponse
 */

/**
 * Retrieve active learners assigned to the authenticated field agent.
 *
 * Includes the latest Job and Social Protection activity statuses,
 * paginated learner records, and status counts across all matching
 * learners before pagination is applied.
 *
 * Search filters apply to both the learner list and status counts.
 *
 * @param Request $request
 * @return JsonResponse
 */
public function index(Request $request): JsonResponse
{
    $validated = $request->validate([
        'search' => ['nullable', 'string', 'max:255'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $fieldAgent = $request->user();

    if (!$fieldAgent || empty($fieldAgent->csc_id)) {
        return response()->json([
            'success' => false,
            'message' => 'Authenticated field agent or CSC ID not found.',
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Build Learner Query With Activity Statuses
    |--------------------------------------------------------------------------
    | Skills: course_completed = 1 means accepted.
    | Job: event_master_type_id 1, 2, or 5.
    | Social Protection: event_master_type_id 3.
    | The latest transaction is selected by highest transaction ID.
    */
    $query = Learner::query()
        ->select('learners.*')
        ->selectRaw("
            CASE
                WHEN learners.course_completed = 1 THEN 'accepted'
                ELSE 'not_started'
            END AS skills_status
        ")
        ->selectSub(function ($subquery) {
            $subquery->from('event_transactions')
                ->selectRaw("
                    CASE
                        WHEN LOWER(TRIM(review_status)) = 'accepted'
                            THEN 'accepted'
                        WHEN LOWER(TRIM(review_status)) = 'rejected'
                            THEN 'rejected'
                        WHEN LOWER(TRIM(review_status))
                            IN ('pending', 'under_review')
                            THEN 'pending'
                        WHEN LOWER(TRIM(review_status)) = 'open'
                            AND LOWER(TRIM(workflow_status)) = 'submitted'
                            THEN 'submitted'
                        ELSE 'not_started'
                    END
                ")
                ->whereColumn(
                    'event_transactions.learner_id',
                    'learners.id'
                )
                ->whereIn(
                    'event_transactions.event_master_type_id',
                    [1, 2, 5]
                )
                ->orderByDesc('event_transactions.id')
                ->limit(1);
        }, 'job_status')
        ->selectSub(function ($subquery) {
            $subquery->from('event_transactions')
                ->selectRaw("
                    CASE
                        WHEN LOWER(TRIM(review_status)) = 'accepted'
                            THEN 'accepted'
                        WHEN LOWER(TRIM(review_status)) = 'rejected'
                            THEN 'rejected'
                        WHEN LOWER(TRIM(review_status))
                            IN ('pending', 'under_review')
                            THEN 'pending'
                        WHEN LOWER(TRIM(review_status)) = 'open'
                            AND LOWER(TRIM(workflow_status)) = 'submitted'
                            THEN 'submitted'
                        ELSE 'not_started'
                    END
                ")
                ->whereColumn(
                    'event_transactions.learner_id',
                    'learners.id'
                )
                ->where(
                    'event_transactions.event_master_type_id',
                    3
                )
                ->orderByDesc('event_transactions.id')
                ->limit(1);
        }, 'social_protection_status')
        ->where('learners.unit', $fieldAgent->csc_id)
        ->where('learners.status', 'active')
        ->whereNotNull('learners.source_created_at');

    /*
    |--------------------------------------------------------------------------
    | Apply Optional Learner Search
    |--------------------------------------------------------------------------
    | Searches learners by first name, last name, normalized mobile number,
    | or phone number. Applies to both learner results and status counts.
    */
    if (!empty($validated['search'])) {
        $search = trim($validated['search']);
        $searchTerm = "%{$search}%";

        $query->where(function ($q) use ($searchTerm) {
            $q->where('learners.first_name', 'like', $searchTerm)
                ->orWhere('learners.last_name', 'like', $searchTerm)
                ->orWhere('learners.normalized_mobile', 'like', $searchTerm)
                ->orWhere('learners.phone_number', 'like', $searchTerm);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Status Counts Before Pagination
    |--------------------------------------------------------------------------
    | Each learner is counted at most once in each category, even if
    | multiple activities match that category.
    */
    $countsQuery = (clone $query)->reorder();

    $statusCounts = \Illuminate\Support\Facades\DB::query()
        ->fromSub($countsQuery, 'learner_statuses')
        ->selectRaw("
            COALESCE(SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(skills_status, ''))) IN (
                        'pending',
                        'under_review',
                        'action_required',
                        'return',
                        'returned',
                        'rejected'
                    )
                    OR LOWER(TRIM(COALESCE(job_status, ''))) IN (
                        'pending',
                        'under_review',
                        'action_required',
                        'return',
                        'returned',
                        'rejected'
                    )
                    OR LOWER(TRIM(COALESCE(social_protection_status, ''))) IN (
                        'pending',
                        'under_review',
                        'action_required',
                        'return',
                        'returned',
                        'rejected'
                    )
                    THEN 1
                    ELSE 0
                END
            ), 0) AS action_required,

            COALESCE(SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(skills_status, ''))) = 'submitted'
                    OR LOWER(TRIM(COALESCE(job_status, ''))) = 'submitted'
                    OR LOWER(TRIM(COALESCE(social_protection_status, ''))) = 'submitted'
                    THEN 1
                    ELSE 0
                END
            ), 0) AS submitted,

            COALESCE(SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(skills_status, ''))) IN (
                        'accepted',
                        'approved',
                        'completed'
                    )
                    OR LOWER(TRIM(COALESCE(job_status, ''))) IN (
                        'accepted',
                        'approved',
                        'completed'
                    )
                    OR LOWER(TRIM(COALESCE(social_protection_status, ''))) IN (
                        'accepted',
                        'approved',
                        'completed'
                    )
                    THEN 1
                    ELSE 0
                END
            ), 0) AS accepted,

            COALESCE(SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(skills_status, ''))) = 'rejected'
                    OR LOWER(TRIM(COALESCE(job_status, ''))) = 'rejected'
                    OR LOWER(TRIM(COALESCE(social_protection_status, ''))) = 'rejected'
                    THEN 1
                    ELSE 0
                END
            ), 0) AS rejected
        ")
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Paginate Learners
    |--------------------------------------------------------------------------
    | Pagination affects only the returned learner records, not the
    | status counts calculated above.
    */
    $learners = $query->paginate(
        $validated['per_page'] ?? 15
    );

    return response()->json([
        'success' => true,
        'message' => 'Learners retrieved successfully.',
        'data' => $learners->items(),
        'pagination' => [
            'current_page' => $learners->currentPage(),
            'last_page' => $learners->lastPage(),
            'per_page' => $learners->perPage(),
            'total' => $learners->total(),
        ],
        'status_counts' => [
            'action_required' => (int) ($statusCounts->action_required ?? 0),
            'submitted' => (int) ($statusCounts->submitted ?? 0),
            'accepted' => (int) ($statusCounts->accepted ?? 0),
            'rejected' => (int) ($statusCounts->rejected ?? 0),
        ],
    ]);
}




/**
 * Retrieve aggregate activity status counts for the authenticated field agent.
 *
 * Calculates Skills, Job, and Social Protection counts across all matching
 * active learners without paginating the learner dataset. The optional search
 * filter matches the Learners API, and each activity counts each learner once.
 *
 * Skills are accepted when course_completed = 1; otherwise not_started.
 * Job and Social Protection use the latest matching event transaction.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function activityCounts(Request $request): JsonResponse
{
    $validated = $request->validate([
        'search' => ['nullable', 'string', 'max:255'],
    ]);

    $fieldAgent = $request->user();

    if (!$fieldAgent || empty($fieldAgent->csc_id)) {
        return response()->json([
            'success' => false,
            'message' => 'Authenticated field agent or CSC ID not found.',
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Build Aggregate Source Query
    |--------------------------------------------------------------------------
    | One row per matching learner. No pagination is applied.
    */
    $query = Learner::query()
        ->select('learners.id')
        ->selectRaw("
            CASE
                WHEN learners.course_completed = 1 THEN 'accepted'
                ELSE 'not_started'
            END AS skills_status
        ")
        ->selectSub(function ($subquery) {
            $subquery->from('event_transactions')
                ->selectRaw("
                    CASE
                        WHEN LOWER(TRIM(review_status)) = 'accepted'
                            THEN 'accepted'
                        WHEN LOWER(TRIM(review_status)) = 'rejected'
                            THEN 'rejected'
                        WHEN LOWER(TRIM(review_status))
                            IN ('pending', 'under_review')
                            THEN 'pending'
                        WHEN LOWER(TRIM(review_status)) = 'open'
                            AND LOWER(TRIM(workflow_status)) = 'submitted'
                            THEN 'submitted'
                        ELSE 'not_started'
                    END
                ")
                ->whereColumn(
                    'event_transactions.learner_id',
                    'learners.id'
                )
                ->whereIn(
                    'event_transactions.event_master_type_id',
                    [1, 2, 5]
                )
                ->orderByDesc('event_transactions.id')
                ->limit(1);
        }, 'job_status')
        ->selectSub(function ($subquery) {
            $subquery->from('event_transactions')
                ->selectRaw("
                    CASE
                        WHEN LOWER(TRIM(review_status)) = 'accepted'
                            THEN 'accepted'
                        WHEN LOWER(TRIM(review_status)) = 'rejected'
                            THEN 'rejected'
                        WHEN LOWER(TRIM(review_status))
                            IN ('pending', 'under_review')
                            THEN 'pending'
                        WHEN LOWER(TRIM(review_status)) = 'open'
                            AND LOWER(TRIM(workflow_status)) = 'submitted'
                            THEN 'submitted'
                        ELSE 'not_started'
                    END
                ")
                ->whereColumn(
                    'event_transactions.learner_id',
                    'learners.id'
                )
                ->where(
                    'event_transactions.event_master_type_id',
                    3
                )
                ->orderByDesc('event_transactions.id')
                ->limit(1);
        }, 'social_protection_status')
        ->where('learners.unit', $fieldAgent->csc_id)
        ->where('learners.status', 'active')
        ->whereNotNull('learners.source_created_at');

    /*
    |--------------------------------------------------------------------------
    | Apply Optional Search
    |--------------------------------------------------------------------------
    | Keeps aggregate counts consistent with the filtered learner listing.
    */
    if (!empty($validated['search'])) {
        $searchTerm = '%' . trim($validated['search']) . '%';

        $query->where(function ($q) use ($searchTerm) {
            $q->where('learners.first_name', 'like', $searchTerm)
                ->orWhere('learners.last_name', 'like', $searchTerm)
                ->orWhere(
                    'learners.normalized_mobile',
                    'like',
                    $searchTerm
                )
                ->orWhere(
                    'learners.phone_number',
                    'like',
                    $searchTerm
                );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Aggregate All Matching Learners
    |--------------------------------------------------------------------------
    | NULL activity statuses are treated as not_started.
    | Each activity is counted independently.
    */
    $counts = \Illuminate\Support\Facades\DB::query()
        ->fromSub($query, 'learner_statuses')
        ->selectRaw("
            COUNT(*) AS total,

            COALESCE(SUM(
                CASE WHEN skills_status = 'accepted'
                    THEN 1 ELSE 0 END
            ), 0) AS skills_accepted,

            COALESCE(SUM(
                CASE WHEN skills_status <> 'accepted'
                    THEN 1 ELSE 0 END
            ), 0) AS skills_not_started,

            COALESCE(SUM(
                CASE WHEN COALESCE(job_status, 'not_started') = 'accepted'
                    THEN 1 ELSE 0 END
            ), 0) AS job_accepted,

            COALESCE(SUM(
                CASE WHEN COALESCE(job_status, 'not_started') = 'pending'
                    THEN 1 ELSE 0 END
            ), 0) AS job_pending,

            COALESCE(SUM(
                CASE WHEN COALESCE(job_status, 'not_started') = 'submitted'
                    THEN 1 ELSE 0 END
            ), 0) AS job_submitted,

            COALESCE(SUM(
                CASE WHEN COALESCE(job_status, 'not_started') = 'rejected'
                    THEN 1 ELSE 0 END
            ), 0) AS job_rejected,

            COALESCE(SUM(
                CASE WHEN COALESCE(job_status, 'not_started') = 'not_started'
                    THEN 1 ELSE 0 END
            ), 0) AS job_not_started,

            COALESCE(SUM(
                CASE WHEN COALESCE(
                    social_protection_status, 'not_started'
                ) = 'accepted'
                    THEN 1 ELSE 0 END
            ), 0) AS social_accepted,

            COALESCE(SUM(
                CASE WHEN COALESCE(
                    social_protection_status, 'not_started'
                ) = 'pending'
                    THEN 1 ELSE 0 END
            ), 0) AS social_pending,

            COALESCE(SUM(
                CASE WHEN COALESCE(
                    social_protection_status, 'not_started'
                ) = 'submitted'
                    THEN 1 ELSE 0 END
            ), 0) AS social_submitted,

            COALESCE(SUM(
                CASE WHEN COALESCE(
                    social_protection_status, 'not_started'
                ) = 'rejected'
                    THEN 1 ELSE 0 END
            ), 0) AS social_rejected,

            COALESCE(SUM(
                CASE WHEN COALESCE(
                    social_protection_status, 'not_started'
                ) = 'not_started'
                    THEN 1 ELSE 0 END
            ), 0) AS social_not_started
        ")
        ->first();

    $total = (int) ($counts->total ?? 0);

    return response()->json([
        'success' => true,
        'message' => 'Activity counts retrieved successfully.',
        'activity_counts' => [
            'skills' => [
                'accepted' => (int) $counts->skills_accepted,
                'not_started' => (int) $counts->skills_not_started,
                'total' => $total,
            ],
            'job' => [
                'accepted' => (int) $counts->job_accepted,
                'pending' => (int) $counts->job_pending,
                'submitted' => (int) $counts->job_submitted,
                'rejected' => (int) $counts->job_rejected,
                'not_started' => (int) $counts->job_not_started,
                'total' => $total,
            ],
            'social_protection' => [
                'accepted' => (int) $counts->social_accepted,
                'pending' => (int) $counts->social_pending,
                'submitted' => (int) $counts->social_submitted,
                'rejected' => (int) $counts->social_rejected,
                'not_started' => (int) $counts->social_not_started,
                'total' => $total,
            ],
        ],
    ]);
}


}
