<?php

namespace App\Http\Controllers\Api\FieldAgent;

use App\Http\Controllers\Controller;
use App\Models\EventTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventActivityController extends Controller
{
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
}
