<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PartnerDashboardApiController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $partner = $request->user('partner');

        $agentCscIds = $partner->fieldAgents()
            ->whereNotNull('csc_id')
            ->select('csc_id');

        return response()->json([
            'success' => true,
            'data' => [
                'total_field_agents' => $partner
                    ->fieldAgents()
                    ->count(),

                'total_learners' => Learner::query()
                    ->whereIn('unit', $agentCscIds)
                    ->count(),

                'total_event_transactions' => $partner
                    ->eventTransactions()
                    ->count(),
            ],
        ]);
    }



    public function growthOverview(Request $request)
    {
        $months = (int) $request->query('months', 6);

        if (!in_array($months, [3, 6, 12])) {
            $months = 6;
        }

        $startDate = now()
            ->startOfMonth()
            ->subMonths($months - 1);

        // Replace table names and date columns with your actual schema.
        $learners = DB::table('learners')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
            ->where('created_at', '>=', $startDate)
            ->groupBy('month')
            ->pluck('total', 'month');

        $fieldAgents = DB::table('field_agents')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
            ->where('created_at', '>=', $startDate)
            ->groupBy('month')
            ->pluck('total', 'month');

        $transactions = DB::table('event_transactions')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
            ->where('created_at', '>=', $startDate)
            ->groupBy('month')
            ->pluck('total', 'month');

        $growth = [];

        for ($i = 0; $i < $months; $i++) {

            $date = $startDate->copy()->addMonths($i);
            $key = $date->format('Y-m');

            $growth[] = [
                'month' => $date->format('M Y'),
                'learners' => (int) ($learners[$key] ?? 0),
                'field_agents' => (int) ($fieldAgents[$key] ?? 0),
                'event_transactions' => (int) ($transactions[$key] ?? 0),
            ];
        }

        return response()->json([
            'success' => true,
            'period' => $months,
            'growth' => $growth,
        ]);
    }



    /**
     * Get the event transaction breakdown grouped by transaction type.
     *
     * Returns each transaction type with its total transaction count
     * for the authenticated partner.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get event transaction counts grouped by master event type.
     *
     * Fetches event type names from event_master_type table
     * and calculates transaction counts for the authenticated partner.
     *
     * @return \Illuminate\Http\JsonResponse
        */
        public function eventTransactionsByType()
        {
            $partnerId = auth('partner')->id();

            $transactions = DB::table('event_transactions as et')
                ->leftJoin(
                    'event_master_type as emt',
                    'et.event_master_type_id',
                    '=',
                    'emt.id'
                )
                ->select(
                    'et.event_master_type_id',
                    'emt.name as type_name',
                    DB::raw('COUNT(et.id) as total')
                )
                ->where('et.partner_id', $partnerId)
                ->groupBy(
                    'et.event_master_type_id',
                    'emt.name'
                )
                ->orderByDesc('total')
                ->get();

            // Convert database records into Recharts-compatible data.
            $types = $transactions->map(function ($item) {
                return [
                    'name' => $item->type_name ?: 'Unknown',
                    'value' => (int) $item->total,
                ];
            });

            return response()->json([
                'success' => true,
                'types' => $types,
                'total' => $types->sum('value'),
            ]);
        }






/**
 * Fetch the five most recent event transactions
 * belonging to the authenticated partner.
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function recentActivities()
{
    $partnerId = auth('partner')->id();

    $activities = DB::table('event_transactions as et')
        ->leftJoin(
            'event_master_type as emt',
            'et.event_master_type_id',
            '=',
            'emt.id'
        )
        ->where('et.partner_id', $partnerId)
        ->select(
            'et.id',
            'et.created_at',
            'emt.name as event_type'
        )
        ->orderByDesc('et.created_at')
        ->limit(4)
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->event_type ?: 'Event Transaction',
                'description' => 'New event transaction recorded',
                'time_label' => Carbon::parse(
                    $item->created_at
                )->diffForHumans(),
            ];
        });

    return response()->json([
        'success' => true,
        'activities' => $activities,
    ]);
}




    public function top10FieldAgents()
    {
        $partner = auth('partner')->user();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $agents = DB::table('field_agents as fa')
            ->leftJoin('learners as l', 'l.unit', '=', 'fa.csc_id')
            ->where('fa.partner_id', $partner->id)
            ->select(
                'fa.id',
                'fa.name',
                DB::raw('COUNT(l.id) as learners')
            )
            ->groupBy('fa.id', 'fa.name')
            ->orderByDesc('learners')
            ->orderBy('fa.name')
            ->limit(10)
            ->get()
            ->values()
            ->map(function ($agent, $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $agent->name,
                    'learners' => (int) $agent->learners,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
}




/**
 * Overall learner age distribution.
 */
public function learnerAgeDistribution()
{
    $ageGroups = DB::query()
        ->fromSub(function ($query) {
            $query->from('learners')
                ->selectRaw("
                    CASE
                        WHEN date_of_birth IS NULL
                            OR date_of_birth > CURDATE()
                            OR date_of_birth < '1900-01-01'
                        THEN 'Unknown'

                        WHEN TIMESTAMPDIFF(
                            YEAR, date_of_birth, CURDATE()
                        ) < 18
                        THEN 'Below 18'

                        WHEN TIMESTAMPDIFF(
                            YEAR, date_of_birth, CURDATE()
                        ) BETWEEN 18 AND 24
                        THEN '18-24'

                        WHEN TIMESTAMPDIFF(
                            YEAR, date_of_birth, CURDATE()
                        ) BETWEEN 25 AND 34
                        THEN '25-34'

                        WHEN TIMESTAMPDIFF(
                            YEAR, date_of_birth, CURDATE()
                        ) BETWEEN 35 AND 44
                        THEN '35-44'

                        ELSE '45+'
                    END AS age_group
                ");
        }, 'learner_age_data')
        ->selectRaw('age_group, COUNT(*) AS total')
        ->groupBy('age_group')
        ->orderByRaw("
            CASE age_group
                WHEN 'Below 18' THEN 1
                WHEN '18-24' THEN 2
                WHEN '25-34' THEN 3
                WHEN '35-44' THEN 4
                WHEN '45+' THEN 5
                ELSE 6
            END
        ")
        ->get();

    $total = (int) $ageGroups->sum('total');

    $data = $ageGroups->map(function ($row) use ($total) {
        return [
            'age_group' => $row->age_group,
            'count' => (int) $row->total,
            'percentage' => $total > 0
                ? round(($row->total / $total) * 100, 1)
                : 0,
        ];
    });

    return response()->json([
        'success' => true,
        'total' => $total,
        'data' => $data,
    ]);
}




public function eventReviewPerformance(Request $request)
{
    $validated = $request->validate([
        'period' => 'nullable|in:day,week,month,year',
    ]);

    $period = $validated['period'] ?? 'month';
    $now = \Carbon\Carbon::now();

    switch ($period) {
        case 'day':
            $start = $now->copy()->subDays(29)->startOfDay();
            break;

        case 'week':
            $start = $now->copy()->startOfWeek()->subWeeks(11);
            break;

        case 'month':
            $start = $now->copy()->startOfMonth()->subMonths(11);
            break;

        case 'year':
            $start = $now->copy()->startOfYear()->subYears(4);
            break;

        default:
            $start = $now->copy()->startOfMonth()->subMonths(11);
    }

    $rows = \Illuminate\Support\Facades\DB::table('event_transactions')
        ->join(
            'learners',
            'event_transactions.learner_id',
            '=',
            'learners.id'
        )
        ->join(
            'partners',
            'event_transactions.partner_id',
            '=',
            'partners.id'
        )
        ->whereNotNull('learners.source_created_at')
        ->whereColumn(
            'learners.program_code',
            'partners.name'
        )
        ->whereBetween('event_transactions.created_at', [
            $start,
            $now,
        ])
        ->selectRaw("
            learners.program_code AS program_code,
            LOWER(TRIM(event_transactions.review_status)) AS status_key,
            COUNT(*) AS total
        ")
        ->groupBy(
            'learners.program_code',
            \Illuminate\Support\Facades\DB::raw(
                'LOWER(TRIM(event_transactions.review_status))'
            )
        )
        ->orderBy('learners.program_code')
        ->get();

    $programs = [];

    $statusTotals = [
        'open' => 0,
        'pending' => 0,
        'accepted' => 0,
        'rejected' => 0,
        'returned' => 0,
    ];

    foreach ($rows as $row) {
        $program = $row->program_code;

        if (!isset($programs[$program])) {
            $programs[$program] = [
                'program_code' => $program,
                'total' => 0,
                'open' => 0,
                'pending' => 0,
                'accepted' => 0,
                'rejected' => 0,
                'returned' => 0,
            ];
        }

        $status = match ($row->status_key) {
            'open' => 'open',
            'pending' => 'pending',
            'accepted' => 'accepted',
            'rejected' => 'rejected',
            'return', 'returned' => 'returned',
            default => null,
        };

        $count = (int) $row->total;

        $programs[$program]['total'] += $count;

        if ($status !== null) {
            $programs[$program][$status] += $count;
            $statusTotals[$status] += $count;
        }
    }

    $programs = array_values($programs);

    return response()->json([
        'period' => $period,
        'from' => $start->toDateString(),
        'to' => $now->toDateString(),
        'total' => array_sum(array_column($programs, 'total')),
        'status_totals' => $statusTotals,
        'programs' => $programs,
    ]);
}




public function indexLearners(\Illuminate\Http\Request $request)
{
    $partner = auth('partner')->user();

    if (!$partner) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    $baseQuery = DB::table('learners')
        ->where('learners.partner_id', $partner->id);

    // Summary counts
    $total = (clone $baseQuery)->count();

    $thisMonth = (clone $baseQuery)
        ->whereMonth('learners.created_at', now()->month)
        ->whereYear('learners.created_at', now()->year)
        ->count();

    $fieldAgents = (clone $baseQuery)
        ->whereNotNull('learners.field_agent_id')
        ->distinct()
        ->count('learners.field_agent_id');

    // Limit page size to a maximum of 100
    $perPage = min(
        max((int) $request->input('per_page', 10), 1),
        100
    );

    // Fetch only the current page
    $learners = (clone $baseQuery)
        ->leftJoin(
            'field_agents as fa',
            'fa.id',
            '=',
            'learners.field_agent_id'
        )
        ->select([
            'learners.id',
            'learners.first_name',
            'learners.last_name',
            'learners.source_created_at',
            'learners.phone_number',
            'learners.created_at',
            'learners.status',
            'learners.unit',
            'fa.name as field_agent',
        ])
        ->orderByDesc('learners.created_at')
        ->orderByDesc('learners.id')
        ->paginate($perPage);

    return response()->json([
        'success' => true,
        'summary' => [
            'total' => $total,
            'this_month' => $thisMonth,
            'field_agents' => $fieldAgents,
        ],
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
