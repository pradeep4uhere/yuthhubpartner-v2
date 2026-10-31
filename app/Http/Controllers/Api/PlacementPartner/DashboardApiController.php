<?php

namespace App\Http\Controllers\Api\PlacementPartner;

use App\Http\Controllers\Controller;
use App\Models\EventTransaction;
use App\Models\Learner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $placementUser = $request->user('partner_placement');

        abort_unless($placementUser, 401);

        // Only Field Agents assigned to this Placement Partner.
        $agents = $placementUser->fieldAgents();

        $agentIds = (clone $agents)
            ->select('id');

        // Unique CSC IDs prevent duplicate learner counting.
        $cscIds = (clone $agents)
            ->whereNotNull('csc_id')
            ->where('csc_id', '!=', '')
            ->select('csc_id')
            ->distinct();

        $transactions = EventTransaction::query()
            ->whereIn('field_agent_id', $agentIds);

        return response()->json([
            'summary' => [
                'total_field_agents' => (clone $agents)->count(),

                'total_learners' => Learner::query()
                    ->whereIn('unit', $cscIds)
                    ->count(),

                'total_event_transactions' =>
                    (clone $transactions)->count(),

                'total_accepted' => (clone $transactions)
                    ->where('review_status', 'accepted')
                    ->count(),
            ],

            'field_agents' => (clone $agents)
                ->select([
                    'id',
                    'name',
                    'csc_id',
                    'state',
                    'district',
                    'status',
                ])
                ->orderBy('name')
                ->limit(10)
                ->get(),
        ]);
    }
}
