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




        public function growthOverview(Request $request): JsonResponse
        {
            // Authenticated Partner
            $partner = $request->user('partner');
            $partnerId = $partner->id;

            // Allowed periods: 3, 6, 12 months
            $months = (int) $request->query('months', 6);

            if (!in_array($months, [3, 6, 12], true)) {
                $months = 6;
            }

            $startDate = now()
                ->startOfMonth()
                ->subMonths($months - 1);

            // Learners Growth (Partner Wise)
            $learners = DB::table('learners')
                ->selectRaw("
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as total
                ")
                ->where('partner_id', $partnerId)
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->pluck('total', 'month');

            // Field Agents Growth (Partner Wise)
            $fieldAgents = DB::table('field_agents')
                ->selectRaw("
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as total
                ")
                ->where('partner_id', $partnerId)
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->pluck('total', 'month');

            // Event Transactions Growth (Partner Wise)
            $transactions = DB::table('event_transactions')
                ->selectRaw("
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as total
                ")
                ->where('partner_id', $partnerId)
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->pluck('total', 'month');

            // Prepare Monthly Growth Data
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
         * Get event transaction counts grouped by master event type
         * for the authenticated partner.
         *
         * @return \Illuminate\Http\JsonResponse
         */
        public function eventTransactionsByType(Request $request): JsonResponse
        {
            // Authenticated Partner
            $partner = $request->user('partner');
            $partnerId = $partner->id;

            // Partner-wise Event Transactions
            $transactions = DB::table('event_transactions as et')
                ->leftJoin(
                    'event_master_type as emt',
                    'et.event_master_type_id',
                    '=',
                    'emt.id'
                )
                ->select(
                    'et.event_master_type_id',
                    'emt.name as type_name'
                )
                ->selectRaw('COUNT(et.id) as total')

                // Only authenticated partner transactions
                ->where('et.partner_id', $partnerId)

                ->groupBy(
                    'et.event_master_type_id',
                    'emt.name'
                )
                ->orderByDesc('total')
                ->get();

            // Recharts Compatible Data
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
         * Fetch the four most recent event transactions
         * belonging to the authenticated partner.
         *
         * @return \Illuminate\Http\JsonResponse
         */
        public function recentActivities(Request $request): JsonResponse
        {
            // Authenticated Partner
            $partner = $request->user('partner');
            $partnerId = $partner->id;

            // Fetch Partner-wise Recent Activities
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
                ->orderByDesc('et.id')
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





        /**
         * Fetch top 10 field agents based on learner count
         * for the authenticated partner.
         *
         * @return \Illuminate\Http\JsonResponse
         */
        public function top10FieldAgents(Request $request): JsonResponse
        {
            // Authenticated Partner
            $partner = $request->user('partner');

            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $partnerId = $partner->id;

            // Partner-wise Top 10 Field Agents
            $agents = DB::table('field_agents as fa')
                ->leftJoin('learners as l', function ($join) use ($partnerId) {

                    $join->on('l.unit', '=', 'fa.csc_id')
                        ->where('l.partner_id', '=', $partnerId);

                })
                ->where('fa.partner_id', $partnerId)
                ->select(
                    'fa.id',
                    'fa.name'
                )
                ->selectRaw('COUNT(l.id) as learners')
                ->groupBy(
                    'fa.id',
                    'fa.name'
                )
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
         * Get learner age distribution for authenticated partner.
         *
         * @return \Illuminate\Http\JsonResponse
         */
        public function learnerAgeDistribution(Request $request): JsonResponse
        {
            // Authenticated Partner
            $partner = $request->user('partner');

            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $partnerId = $partner->id;

            // Partner-wise Age Distribution
            $ageGroups = DB::table('learners')
                ->where('partner_id', $partnerId)
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
                    END AS age_group,
                    COUNT(*) AS total
                ")
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

            // Total Learners
            $total = (int) $ageGroups->sum('total');

            // Format Response
            $data = $ageGroups->map(function ($row) use ($total) {

                return [
                    'age_group' => $row->age_group,
                    'count' => (int) $row->total,
                    'percentage' => $total > 0
                        ? round(
                            ((int) $row->total / $total) * 100,
                            1
                        )
                        : 0,
                ];

            })->values();

            return response()->json([
                'success' => true,
                'total' => $total,
                'data' => $data,
            ]);
        }






            /**
             * Partner Center Wise Event Review Performance.
             *
             * Supports:
             * - All Centers
             * - Specific Partner Center
             * - Day, Week, Month, Year
             */
            public function eventReviewPerformance(Request $request): JsonResponse
            {
                $partner = $request->user('partner');

                if (!$partner) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated.',
                    ], 401);
                }

                $partnerId = $partner->id;

                // Validate Filters
                $validated = $request->validate([
                    'period' => 'nullable|in:day,week,month,year',
                    'partner_center_id' => [
                        'nullable',
                        'integer',
                        'exists:partner_centers,id',
                    ],
                ]);

                $period = $validated['period'] ?? 'month';

                $centerId = $validated['partner_center_id'] ?? null;

                // Verify selected center belongs to logged-in partner
                if ($centerId !== null) {

                    $centerExists = DB::table('partner_centers')
                        ->where('id', $centerId)
                        ->where('partner_id', $partnerId)
                        ->exists();

                    if (!$centerExists) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid partner center.',
                        ], 403);
                    }
                }

                $now = now();

                // Date Range
                switch ($period) {
                    case 'day':
                        $start = $now->copy()
                            ->subDays(29)
                            ->startOfDay();
                        break;

                    case 'week':
                        $start = $now->copy()
                            ->startOfWeek()
                            ->subWeeks(11);
                        break;

                    case 'year':
                        $start = $now->copy()
                            ->startOfYear()
                            ->subYears(4);
                        break;

                    default:
                        $start = $now->copy()
                            ->startOfMonth()
                            ->subMonths(11);
                }

                // Fetch Partner Centers for Dropdown
                $centers = DB::table('partner_centers')
                    ->where('partner_id', $partnerId)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get();

                // Partner Center Wise Event Transactions
                $rows = DB::table('event_transactions as et')

                    ->join('field_agents as fa', function ($join) {
                        $join->on(
                            'et.field_agent_id',
                            '=',
                            'fa.id'
                        );
                    })

                    ->join('partner_centers as pc', function ($join) {
                        $join->on(
                            'fa.partner_center_id',
                            '=',
                            'pc.id'
                        );
                    })

                    // Security: logged-in partner only
                    ->where('et.partner_id', $partnerId)
                    ->where('fa.partner_id', $partnerId)
                    ->where('pc.partner_id', $partnerId)

                    // Optional Center Filter
                    ->when($centerId !== null, function ($query) use ($centerId) {
                        $query->where('pc.id', $centerId);
                    })

                    // Date Filter
                    ->whereBetween('et.created_at', [
                        $start,
                        $now,
                    ])

                    ->select(
                        'pc.id as partner_center_id',
                        'pc.name as center_name'
                    )

                    ->selectRaw("
                        LOWER(TRIM(et.review_status)) AS status_key,
                        COUNT(*) AS total
                    ")

                    ->groupBy(
                        'pc.id',
                        'pc.name',
                        DB::raw('LOWER(TRIM(et.review_status))')
                    )

                    ->orderBy('pc.name')
                    ->get();

                // Status Totals
                $statusTotals = [
                    'open' => 0,
                    'pending' => 0,
                    'accepted' => 0,
                    'rejected' => 0,
                    'returned' => 0,
                ];

                $centerData = [];

                // Process Results
                foreach ($rows as $row) {

                    $id = $row->partner_center_id;

                    if (!isset($centerData[$id])) {

                        $centerData[$id] = [
                            'partner_center_id' => $id,
                            'center_name' => $row->center_name,
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

                    $centerData[$id]['total'] += $count;

                    if ($status !== null) {
                        $centerData[$id][$status] += $count;
                        $statusTotals[$status] += $count;
                    }
                }

                return response()->json([
                    'success' => true,

                    'period' => $period,

                    'from' => $start->toDateString(),
                    'to' => $now->toDateString(),

                    'selected_center_id' => $centerId,

                    'centers' => $centers,

                    'total' => array_sum(
                        array_column($centerData, 'total')
                    ),

                    'status_totals' => $statusTotals,

                    'data' => array_values($centerData),
                ]);
            }





        /**
         * Get paginated learners for authenticated partner.
         *
         * @return \Illuminate\Http\JsonResponse
         */
        public function indexLearners(Request $request): JsonResponse
        {
            // Authenticated Partner
            $partner = $request->user('partner');

            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $partnerId = $partner->id;

            // Base Partner Query
            $baseQuery = DB::table('learners as l')
                ->where('l.partner_id', $partnerId);

            // Total Learners
            $total = (clone $baseQuery)->count();

            // Current Month Date Range
            $monthStart = now()->startOfMonth();
            $nextMonthStart = $monthStart->copy()->addMonth();

            // This Month Learners
            $thisMonth = (clone $baseQuery)
                ->where('l.created_at', '>=', $monthStart)
                ->where('l.created_at', '<', $nextMonthStart)
                ->count();

            // Total Unique Field Agents
            $fieldAgents = (clone $baseQuery)
                ->whereNotNull('l.field_agent_id')
                ->distinct()
                ->count('l.field_agent_id');

            // Pagination Limit
            $perPage = min(
                max((int) $request->input('per_page', 10), 1),
                100
            );

            // Partner-wise Learners List
            $learners = (clone $baseQuery)
                ->leftJoin('field_agents as fa', function ($join) use ($partnerId) {

                    $join->on('fa.id', '=', 'l.field_agent_id')
                        ->where('fa.partner_id', '=', $partnerId);

                })
                ->select([
                    'l.id',
                    'l.first_name',
                    'l.last_name',
                    'l.source_created_at',
                    'l.phone_number',
                    'l.created_at',
                    'l.status',
                    'l.unit',
                    'fa.name as field_agent',
                ])
                ->orderByDesc('l.created_at')
                ->orderByDesc('l.id')
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
