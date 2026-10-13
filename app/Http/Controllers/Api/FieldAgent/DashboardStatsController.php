<?php

namespace App\Http\Controllers\Api\FieldAgent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardStatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agent = $request->user('field_agent');

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $agentId = $agent->getAuthIdentifier();
        $cscId = $agent->csc_id;

        $learners = DB::table('learners')
            ->where('unit', $cscId);

        $totalLearners = $cscId
            ? (clone $learners)->count()
            : 0;

        $completedCourses = $cscId
            ? (clone $learners)
                ->where('course_completed', 1)
                ->count()
            : 0;

        $transactions = DB::table('event_transactions')
            ->where('field_agent_id', $agentId);

        $totalTransactions = (clone $transactions)->count();

        $verifiedTransactions = (clone $transactions)
            ->where('review_status', 'Accepted')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics retrieved successfully.',
            'data' => [
                'my_learners' => $totalLearners,
                'all_event_transactions' => $totalTransactions,
                'course_completed' => $completedCourses,
                'event_verification' => $verifiedTransactions,
            ],
        ]);
    }
}
