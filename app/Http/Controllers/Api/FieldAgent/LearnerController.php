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


}
