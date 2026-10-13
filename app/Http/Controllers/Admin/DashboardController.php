<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function data(): JsonResponse
    {
        $learners = DB::table('learners');

        // Latest learners created or updated during last 7 days.
        $recentLearners = DB::table('learners')
            ->where(function ($query) {
                $query->where('created_at', '>=', now()->subDays(700))
                    ->orWhere('updated_at', '>=', now()->subDays(700));
            })
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();


            $upcomingEvents = DB::table('event_transactions as et')
            ->leftJoin('field_agents as fa', 'fa.id', '=', 'et.field_agent_id')
            ->leftJoin('event_master as em', function ($join) {
                $join->on(
                    'em.id',
                    '=',
                    'et.event_master_id'
                );
            })
            ->select(
                'et.id',
                'et.beneficiary_name as learner_name',
                'et.beneficiary_phone_number as learner_phone',
                'fa.name as field_agent_name',
                'fa.sakhi_id',
                'fa.state',
                'fa.district',
                'et.workflow_status',
                'et.review_status',
                'et.event_type_name',
                'et.event_category_name',
                'et.event_create_date',
                'et.event_date_submitted',
                'em.event_name',
                'et.updated_at'
            )
            ->orderByDesc('et.updated_at')
            ->limit(3)
            ->get();

        // Most recently created event.
        $latestEvent = DB::table('event_master')
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'success' => true,
            'generated_at' => now()->toIso8601String(),

            'metrics' => [
                'learners' => $learners->count(),
                // Connect these to the actual tables/relationships.
                'field_agents' => DB::table('field_agents')->count(),
                'partners' => DB::table('partners')->count(),
                'event_transactions' =>
                    DB::table('event_transactions')->count(),
            ],

            'recent_learners' => $recentLearners,
            'latest_event' => $latestEvent,
            'upcoming_events' => $upcomingEvents,
        ]);
    }




    /**
     * Monthly learner registration statistics by gender.
     */
    public function monthlyGender(Request $request)
    {
        $period = $request->query('period', 'last_6_months');

        $now = Carbon::now();

        switch ($period) {
            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $numberOfMonths = 1;
                break;

            case 'this_quarter':
                $startDate = $now->copy()->startOfQuarter();
                $numberOfMonths = 3;
                break;

            case 'this_year':
                $startDate = $now->copy()->startOfYear();
                $numberOfMonths = $now->month;
                break;

            case 'last_6_months':
            default:
                $startDate = $now->copy()->subMonths(5)->startOfMonth();
                $numberOfMonths = 6;
                break;
        }

        $dateExpression = 'COALESCE(source_created_at, created_at)';

        $rows = DB::table('learners')
            ->selectRaw("
                YEAR($dateExpression) AS year,
                MONTH($dateExpression) AS month,
                LOWER(gender) AS gender,
                COUNT(*) AS total
            ")
            ->whereRaw("$dateExpression >= ?", [
                $startDate->toDateTimeString(),
            ])
            ->whereRaw("$dateExpression <= ?", [
                $now->toDateTimeString(),
            ])
            ->groupByRaw("
                YEAR($dateExpression),
                MONTH($dateExpression),
                LOWER(gender)
            ")
            ->orderByRaw("
                YEAR($dateExpression),
                MONTH($dateExpression)
            ")
            ->get();

        // Prepare every month, including months with zero registrations.
        $monthlyData = [];

        for ($i = 0; $i < $numberOfMonths; $i++) {
            $date = $startDate->copy()->addMonths($i);

            $key = $date->format('Y-m');

            $monthlyData[$key] = [
                'year' => (int) $date->year,
                'month' => (int) $date->month,
                'label' => $date->format('M'),
                'male' => 0,
                'female' => 0,
                'other' => 0,
            ];
        }

        foreach ($rows as $row) {
            $key = sprintf('%04d-%02d', $row->year, $row->month);

            if (!isset($monthlyData[$key])) {
                continue;
            }

            $gender = strtolower((string) $row->gender);

            if (in_array($gender, ['male', 'female', 'other'], true)) {
                $monthlyData[$key][$gender] = (int) $row->total;
            }
        }

        return response()->json([
            'success' => true,
            'period' => $period,
            'data' => array_values($monthlyData),
        ]);
    }





    /**
 * Overall event transaction counts by review status.
 */
public function eventTransactionStatus()
{
    $statusCounts = DB::table('event_transactions')
        ->selectRaw("
            COALESCE(NULLIF(TRIM(review_status), ''), 'Unknown') AS status,
            COUNT(*) AS total
        ")
        ->groupBy('review_status')
        ->orderBy('status')
        ->get();

    $total = (int) $statusCounts->sum('total');

    $data = $statusCounts->map(function ($row) use ($total) {
        return [
            'status' => $row->status,
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




    
/**
 * Overall learner gender distribution.
 */
public function learnerGenderStatus()
{
    $genderCounts = DB::query()
        ->fromSub(function ($query) {
            $query->from('learners')
                ->selectRaw("
                    CASE
                        WHEN LOWER(TRIM(gender)) IN ('male', 'female', 'other')
                            THEN LOWER(TRIM(gender))
                        ELSE 'unknown'
                    END AS gender
                ");
        }, 'learner_gender_data')
        ->selectRaw('gender, COUNT(*) AS total')
        ->groupBy('gender')
        ->orderBy('gender')
        ->get();

    $total = (int) $genderCounts->sum('total');

    $data = $genderCounts->map(function ($row) use ($total) {
        $gender = $row->gender;

        return [
            'gender' => match ($gender) {
                'male' => 'Male',
                'female' => 'Female',
                'other' => 'Other',
                default => 'Unknown',
            },
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





/**
 * Event transaction counts by day, week, or month.
 */
public function eventTransactionCounts(Request $request)
{
    $period = $request->query('period', 'day');

    if (!in_array($period, ['day', 'week', 'month'], true)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid period. Use day, week, or month.',
        ], 422);
    }

    $now = Carbon::now();

    switch ($period) {
        case 'week':
            $startDate = $now->copy()->startOfWeek()->subWeeks(11);
            $numberOfBuckets = 12;
            $bucketExpression = 'DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY)';
            break;

        case 'month':
            $startDate = $now->copy()->startOfMonth()->subMonths(11);
            $numberOfBuckets = 12;
            $bucketExpression = "DATE_FORMAT(created_at, '%Y-%m-01')";
            break;

        default:
            $startDate = $now->copy()->subDays(29)->startOfDay();
            $numberOfBuckets = 30;
            $bucketExpression = 'DATE(created_at)';
            break;
    }

    $rows = DB::query()
        ->fromSub(function ($query) use (
            $bucketExpression,
            $startDate,
            $now
        ) {
            $query->from('event_transactions')
                ->select('created_at')
                ->selectRaw("$bucketExpression AS bucket")
                ->whereNotNull('created_at')
                ->whereBetween('created_at', [
                    $startDate->toDateTimeString(),
                    $now->toDateTimeString(),
                ]);
        }, 'transaction_dates')
        ->selectRaw('bucket, COUNT(*) AS total')
        ->groupBy('bucket')
        ->orderBy('bucket')
        ->get();

    $counts = [];

    foreach ($rows as $row) {
        $key = Carbon::parse($row->bucket)->format('Y-m-d');
        $counts[$key] = (int) $row->total;
    }

    $data = [];

    for ($i = 0; $i < $numberOfBuckets; $i++) {
        if ($period === 'week') {
            $date = $startDate->copy()->addWeeks($i);
        } elseif ($period === 'month') {
            $date = $startDate->copy()->addMonths($i);
        } else {
            $date = $startDate->copy()->addDays($i);
        }

        $key = $date->format('Y-m-d');

        $data[] = [
            'date' => $key,
            'label' => $period === 'month'
                ? $date->format('M y')
                : ($period === 'week' ? $date->format('d M') : $date->format('d M')),
            'count' => $counts[$key] ?? 0,
        ];
    }

    return response()->json([
        'success' => true,
        'period' => $period,
        'total' => array_sum(array_column($data, 'count')),
        'data' => $data,
    ]);
}








public function learnersByProgramme()
{
    $programmeCounts = DB::query()
        ->fromSub(function ($query) {
            $query->from('learners')
                ->selectRaw("
                    CASE
                        WHEN program_code IS NULL
                            OR TRIM(program_code) = ''
                        THEN 'Unassigned'
                        ELSE UPPER(TRIM(program_code))
                    END AS programme
                ");
        }, 'learner_programmes')
        ->selectRaw('programme, COUNT(*) AS total')
        ->groupBy('programme')
        ->orderByDesc('total')
        ->orderBy('programme')
        ->get();

    $total = (int) $programmeCounts->sum('total');

    $data = $programmeCounts->map(function ($row) use ($total) {
        return [
            'programme' => $row->programme,
            'count' => (int) $row->total,
            'percentage' => $total > 0
                ? round(($row->total / $total) * 100, 1)
                : 0,
        ];
    })->values();

    return response()->json([
        'success' => true,
        'total' => $total,
        'data' => $data,
    ]);
}





public function top10FieldAgents()
{
    $agents = DB::connection('mysql')
        ->table('field_agents as fa')
        ->join('learners as l', 'l.unit', '=', 'fa.csc_id')
        ->select(
            'fa.id',
            'fa.name'
        )
        ->selectRaw('COUNT(*) AS total_learners')
        ->groupBy('fa.id', 'fa.name')
        ->orderByDesc('total_learners')
        ->orderBy('fa.name')
        ->limit(10)
        ->get();

    $data = $agents->values()->map(function ($agent, $index) {
        return [
            'rank' => $index + 1,
            'field_agent_id' => (int) $agent->id,
            'name' => $agent->name ?: 'Field Agent ' . $agent->id,
            'learners' => (int) $agent->total_learners,
        ];
    });

    return response()->json([
        'success' => true,
        'total_agents' => $data->count(),
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



}
