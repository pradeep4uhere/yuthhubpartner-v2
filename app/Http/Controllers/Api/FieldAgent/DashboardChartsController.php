<?php

namespace App\Http\Controllers\Api\FieldAgent;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardChartsController extends Controller
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

        $period = $request->query('period', 'day');

        if (!in_array($period, ['day', 'week', 'month'], true)) {
            $period = 'day';
        }

        /*
         * Event activity trend.
         * Day   = last 7 days
         * Week  = last 8 weeks
         * Month = last 6 months
         */
        $today = Carbon::today();

        if ($period === 'week') {
            $start = $today->copy()->startOfWeek()->subWeeks(7);
            $end = $today->copy()->endOfWeek();
            $groupExpression = 'YEARWEEK(created_at, 1)';
            $bucketCount = 8;
        } elseif ($period === 'month') {
            $start = $today->copy()->startOfMonth()->subMonths(5);
            $end = $today->copy()->endOfMonth();
            $groupExpression = "DATE_FORMAT(created_at, '%Y-%m')";
            $bucketCount = 6;
        } else {
            $start = $today->copy()->subDays(6);
            $end = $today->copy();
            $groupExpression = 'DATE(created_at)';
            $bucketCount = 7;
        }

        $trendRows = DB::table('event_transactions')
            ->selectRaw("$groupExpression as bucket, COUNT(*) as total")
            ->where('field_agent_id', $agentId)
            ->whereBetween('created_at', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->groupByRaw($groupExpression)
            ->get()
            ->keyBy(fn ($row) => (string) $row->bucket);

        $trend = [];

        if ($period === 'week') {
            $cursor = $start->copy()->startOfWeek();

            for ($i = 0; $i < $bucketCount; $i++) {
                $key = (string) ($cursor->isoWeekYear * 100 + $cursor->isoWeek);

                $trend[] = [
                    'label' => $cursor->format('d M'),
                    'total' => (int) ($trendRows[$key]->total ?? 0),
                ];

                $cursor->addWeek();
            }
        } elseif ($period === 'month') {
            $cursor = $start->copy()->startOfMonth();

            for ($i = 0; $i < $bucketCount; $i++) {
                $key = $cursor->format('Y-m');

                $trend[] = [
                    'label' => $cursor->format('M Y'),
                    'total' => (int) ($trendRows[$key]->total ?? 0),
                ];

                $cursor->addMonth();
            }
        } else {
            $cursor = $start->copy()->startOfDay();

            for ($i = 0; $i < $bucketCount; $i++) {
                $key = $cursor->toDateString();

                $trend[] = [
                    'label' => $cursor->format('d M'),
                    'total' => (int) ($trendRows[$key]->total ?? 0),
                ];

                $cursor->addDay();
            }
        }

        /*
         * Event review status breakdown.
         */
        $statusCounts = DB::table('event_transactions')
            ->select('review_status')
            ->selectRaw('COUNT(*) as total')
            ->where('field_agent_id', $agentId)
            ->groupBy('review_status')
            ->get();

        $statusMap = $statusCounts->mapWithKeys(function ($row) {
            return [
                strtolower(trim((string) $row->review_status)) => (int) $row->total,
            ];
        });

        $reviewStatuses = ['Open', 'Pending', 'Accepted', 'Rejected', 'Return'];

        $reviewStatus = collect($reviewStatuses)->map(function ($status) use ($statusMap) {
            return [
                'name' => $status,
                'value' => (int) ($statusMap[strtolower($status)] ?? 0),
            ];
        })->values();

        /*
         * Course completion for learners assigned to this center.
         * Change UNIT_INSTITUTE only if your actual database column differs.
         */
        $learners = DB::table('learners')
            ->where('unit', $cscId);

        $totalLearners = $cscId ? (clone $learners)->count() : 0;

        $completed = $cscId
            ? (clone $learners)->where('course_completed', 1)->count()
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'activity_trend' => $trend,
                'review_status' => $reviewStatus,
                'course_completion' => [
                    ['name' => 'Completed', 'value' => $completed],
                    ['name' => 'Pending', 'value' => max(0, $totalLearners - $completed)],
                ],
            ],
        ]);
    }
}
