<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\FieldAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LearnerApiController extends Controller
{
    /**
     * Return learner dashboard summary statistics.
     */
    public function summary()
    {
        $today = now()->toDateString();

        $totalLearners = Cache::remember('admin_learners_total', 300, function () {
            return Learner::count();
        });

        $todayLearners = Learner::whereDate('created_at', $today)->count();

        $last7Days = Learner::where('created_at', '>=', now()->subDays(6)->startOfDay())->count();

        $previous7Days = Learner::whereBetween('created_at', [
            now()->subDays(13)->startOfDay(),
            now()->subDays(7)->endOfDay(),
        ])->count();

        $weeklyGrowth = $previous7Days > 0
            ? round((($last7Days - $previous7Days) / $previous7Days) * 100, 2)
            : ($last7Days > 0 ? 100 : 0);

        return response()->json([
            'success' => true,
            'data' => [
                'total_learners' => $totalLearners,
                'today_learners' => $todayLearners,
                'last_7_days' => $last7Days,
                'weekly_growth' => $weeklyGrowth,
                'completed_learners' => Cache::remember('admin_learners_completed', 300, function () {
                    return Learner::where('course_completed', 1)->count();
                }),
                'pending_learners' => Cache::remember('admin_learners_pending', 300, function () {
                    return Learner::where('course_completed', 0)->count();
                }),
                'active_learners' => Cache::remember('admin_learners_active', 300, function () {
                    return Learner::where('status', 'active')->count();
                }),
                'unassigned_learners' => Cache::remember('admin_learners_unassigned', 300, function () {
                    return Learner::whereNull('field_agent_id')->count();
                }),
            ],
        ]);
    }

    /**
     * Return learner registration trend for the last seven days.
     */
    public function analytics()
    {
        $registrationTrend = Learner::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'registration_trend' => $registrationTrend,
            ],
        ]);
    }

    /**
     * Return paginated learners with search and advanced filters.
     */
    public function listing(Request $request)
    {
        $filters = $request->only([
            'search',
            'partner_id',
            'partner_center_id',
            'field_agent_id',
            'program_code',
            'course_completed',
            'status',
            'date_from',
            'date_to',
        ]);

        $query = Learner::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'gender',
                'phone_number',
                'normalized_mobile',
                'program_code',
                'partner_id',
                'partner_center_id',
                'field_agent_id',
                'city',
                'state',
                'course_completed',
                'status',
                'created_at',
            ])
            ->with([
                'partner',
                'partnerCenter',
                'fieldAgent',
            ]);

        $this->applyFilters($query, $filters);

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);

        $learners = $query->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $learners,
        ]);
    }

    /**
     * Return dropdown options for learner advanced filters.
     */
    public function filterOptions()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'partners' => Partner::select('id', 'name')->orderBy('name')->get(),
                'partner_centers' => PartnerCenter::select('id', 'name', 'partner_id')
                    ->orderBy('name')
                    ->get(),
                'field_agents' => FieldAgent::select('id', 'name', 'partner_id', 'partner_center_id')
                    ->orderBy('name')
                    ->get(),
            ],
        ]);
    }

    /**
     * Apply the same search and advanced filters used by the Learner page.
     */
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', $search . '%')
                    ->orWhere('last_name', 'LIKE', $search . '%')
                    ->orWhere('normalized_mobile', 'LIKE', $search . '%')
                    ->orWhere('program_code', 'LIKE', $search . '%');
            });
        }

        if (!empty($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }

        if (!empty($filters['partner_center_id'])) {
            $query->where('partner_center_id', $filters['partner_center_id']);
        }

        if (!empty($filters['field_agent_id'])) {
            $query->where('field_agent_id', $filters['field_agent_id']);
        }

        if (!empty($filters['program_code'])) {
            $query->where('program_code', 'LIKE', $filters['program_code'] . '%');
        }

        if (isset($filters['course_completed']) && $filters['course_completed'] !== '') {
            $query->where('course_completed', $filters['course_completed']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
