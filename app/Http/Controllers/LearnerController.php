<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\FieldAgent;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\View;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ExportLearnersJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Services\ExportService;


class LearnerController extends Controller
{


 public function index(Request $request)
    {
        $filters = [
            'search'           => trim((string) $request->input('search')),
            'partner_id'       => $request->input('partner_id'),
            'partner_center_id' => $request->input('partner_center_id'),
            'field_agent_id'   => $request->input('field_agent_id'),
            'program_code'     => trim((string) $request->input('program_code')),
            'course_completed' => $request->input('course_completed'),
            'status'           => $request->input('status'),
            'date_from'        => $request->input('date_from'),
            'date_to'          => $request->input('date_to'),
        ];

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $baseQuery = Learner::query();

        $this->applyFilters($baseQuery, $filters);


        /*
        |--------------------------------------------------------------------------
        | KPI
        |--------------------------------------------------------------------------
        */

        $totalLearners = Cache::remember(
            'admin.learners.total',
            now()->addMinutes(5),
            fn () => Learner::count()
        );

        $todayLearners = Learner::query()
            ->whereDate('created_at', today())
            ->count();

        $lastSevenDays = Learner::query()
            ->whereBetween('created_at', [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
            ])
            ->count();

        $previousSevenDays = Learner::query()
            ->whereBetween('created_at', [
                now()->subDays(13)->startOfDay(),
                now()->subDays(7)->endOfDay(),
            ])
            ->count();

        $weeklyGrowth = 0;

        if ($previousSevenDays > 0) {
            $weeklyGrowth = round(
                (($lastSevenDays - $previousSevenDays) / $previousSevenDays) * 100,
                1
            );
        }

        $completedLearners = Cache::remember(
            'admin.learners.completed',
            now()->addMinutes(5),
            fn () => Learner::where('course_completed', 1)->count()
        );

        $pendingLearners = Cache::remember(
            'admin.learners.pending',
            now()->addMinutes(5),
            fn () => Learner::where('course_completed', 0)->count()
        );

        $activeLearners = Cache::remember(
            'admin.learners.active',
            now()->addMinutes(5),
            fn () => Learner::where('status', 'active')->count()
        );

        $unassignedLearners = Cache::remember(
            'admin.learners.unassigned',
            now()->addMinutes(5),
            fn () => Learner::whereNull('field_agent_id')->count()
        );


        /*
        |--------------------------------------------------------------------------
        | Last 7 Days Chart
        |--------------------------------------------------------------------------
        */

        $trendStart = now()->subDays(6)->startOfDay();

        $trendData = Learner::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $trendStart)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->pluck('total', 'date');

        $registrationTrend = collect(range(6, 0))
            ->map(function ($daysAgo) use ($trendData) {

                $date = now()->subDays($daysAgo);

                return [
                    'label' => $date->format('D'),
                    'date'  => $date->format('Y-m-d'),
                    'total' => (int) ($trendData[$date->format('Y-m-d')] ?? 0),
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | Main Learner Listing
        |--------------------------------------------------------------------------
        */

        $learners = (clone $baseQuery)
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
                'partner:id,name',
                'partnerCenter:id,name',
                'fieldAgent:id,name,sakhi_id',
            ])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Filter Data
        |--------------------------------------------------------------------------
        */

        $partners = Partner::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $partnerCenters = PartnerCenter::query()
            ->select(['id', 'name', 'partner_id'])
            ->orderBy('name')
            ->get();

        $fieldAgents = FieldAgent::query()
            ->select(['id', 'name', 'sakhi_id'])
            ->orderBy('name')
            ->get();

       

        return view('admin.learners.index', compact(
            'learners',
            'partners',
            'partnerCenters',
            'fieldAgents',
            'filters',
            'totalLearners',
            'todayLearners',
            'lastSevenDays',
            'weeklyGrowth',
            'completedLearners',
            'pendingLearners',
            'activeLearners',
            'unassignedLearners',
            'registrationTrend',
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    */

    private function applyFilters($query, array $filters): void
    {
        if ($filters['search'] !== '') {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {

                $q->where('first_name', 'like', "{$search}%")
                    ->orWhere('last_name', 'like', "{$search}%")
                    ->orWhere('normalized_mobile', 'like', "{$search}%")
                    ->orWhere('program_code', 'like', "{$search}%");
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

        if ($filters['program_code'] !== '') {
            $query->where(
                'program_code',
                'like',
                $filters['program_code'] . '%'
            );
        }

        if ($filters['course_completed'] !== null && $filters['course_completed'] !== '') {
            $query->where(
                'course_completed',
                $filters['course_completed']
            );
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where(
                'created_at',
                '>=',
                Carbon::parse($filters['date_from'])->startOfDay()
            );
        }

        if (!empty($filters['date_to'])) {
            $query->where(
                'created_at',
                '<=',
                Carbon::parse($filters['date_to'])->endOfDay()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */
        public function export(Request $request, ExportService $exportService)
        {
            $admin = auth('admin')->user();

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

            $exportService->create($admin, [
                'type' => 'learners',
                'scope_type' => 'system',
                'scope_id' => null,
                'title' => 'Learners Export',
                'filters' => $filters,
            ]);

            return back()->with(
                'status',
                'Learner export has been queued. You can download it from Downloads.'
            );
        }


   public function old_export(Request $request)
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

        $export = LearnerExport::create([
            'admin_id' => auth('admin')->id(),
            'status' => 'queued',
            'filters' => $filters,
        ]);

        \App\Jobs\ExportLearnersJob::dispatch(
            $export->id
        );

        return back()->with(
            'status',
            'Learner export has been queued. Your export will appear in Export History once it is ready.'
        );
    }




    public function downloadExport(LearnerExport $export)
    {
        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        $admin = auth('admin')->user();

        abort_unless(
            $admin,
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Ownership protection
        |--------------------------------------------------------------------------
        |
        | Admin can download their own exports.
        |
        */

        abort_unless(
            (int) $export->admin_id === (int) $admin->id,
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $export->status === 'completed',
            404
        );

        /*
        |--------------------------------------------------------------------------
        | File existence
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $export->file_path &&
            Storage::disk('local')->exists($export->file_path),
            404
        );

        return Storage::disk('local')->download(
            $export->file_path,
            $export->file_name,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' =>
                    'attachment; filename="' .
                    $export->file_name .
                    '"',
            ]
        );
    }



    /**
     * Display a listing of the resource.
     */
  public function old_index(Request $request): \Illuminate\View\View
{
    $filters = $request->validate([
        'search' => ['nullable', 'string', 'max:100'],
        'status' => ['nullable', Rule::in(['active', 'inactive'])],
        'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
        'course_completed' => ['nullable', Rule::in(['0', '1'])],
    ]);

    $learners = Learner::query()
        ->with([
            'partner:id,name,partner_code',
            'partnerCenter:id,name,partner_code',
            'fieldAgent:id,name,sakhi_id',
        ])

        ->when($filters['search'] ?? null, function ($query, string $search): void {
            $query->where(function ($query) use ($search): void {

                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('normalized_mobile', 'like', "%{$search}%")
                    ->orWhere('program_code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")

                    ->orWhereHas('partner', fn ($relation) =>
                        $relation->where('name', 'like', "%{$search}%"))

                    ->orWhereHas('partnerCenter', fn ($relation) =>
                        $relation->where('name', 'like', "%{$search}%"))

                    ->orWhereHas('fieldAgent', fn ($relation) =>
                        $relation->where('name', 'like', "%{$search}%")
                            ->orWhere('sakhi_id', 'like', "%{$search}%"));
            });
        })

        ->when(
            $filters['partner_id'] ?? null,
            fn ($query, int $partnerId) =>
            $query->where('partner_id', $partnerId)
        )

        ->when(
            $filters['status'] ?? null,
            fn ($query, string $status) =>
            $query->where('status', $status)
        )

        ->when(
            array_key_exists('course_completed', $filters)
                && $filters['course_completed'] !== null,
            fn ($query) =>
            $query->where('course_completed', (bool) $filters['course_completed'])
        )

        ->orderBy('first_name')
        ->paginate(10)
        ->withQueryString();

    return view('admin.learners.index', [
        'learners' => $learners,
        'filters' => $filters,
        'partners' => Partner::orderBy('name')
            ->get(['id', 'name', 'partner_code']),
    ]);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $learner = new Learner();

        return view('admin.learners.create', [
            'pageTitle'  => 'Add New Learner',
            'formAction' => route('admin.learners.store'),
            'formMethod' => 'POST',
            'learner'=>$learner,
            'partners' => Partner::orderBy('name')->get(),
            'fieldAgents' => FieldAgent::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $data['normalized_mobile'] = $this->normalizeMobile(
            $data['phone_number'] ?? null
        );

        Learner::create($data);

        return redirect()
            ->route('admin.learners.index')
            ->with('status', "Learner created successfully.");
    }

    /**
     * Show the form for editing the resource.
     */
    public function edit(Learner $learner)
    {
        return view('admin.learners.edit', [
        'pageTitle'  => 'Edit Learner',
        'formAction' => route('admin.learners.update', $learner),
        'formMethod' => 'PUT',
        'learner'    => $learner,

        'partners'   => Partner::orderBy('name')->get(),
        'fieldAgents' => FieldAgent::orderBy('name')->get(),
    ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Learner $learner): RedirectResponse
    {
        $data = $this->validatedData($request, $learner);

        $data['normalized_mobile'] = $this->normalizeMobile(
            $data['phone_number'] ?? null
        );

        $learner->update($data);

        return redirect()
            ->route('admin.learners.index')
            ->with('status', "Learner updated successfully.");
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Learner $learner): RedirectResponse
    {
        $learner->delete();

        return redirect()
            ->route('admin.learners.index')
            ->with('status', "Learner deleted successfully.");
    }

    /**
     * Validation.
     */
    private function validatedData(Request $request, ?Learner $learner = null): array
    {
        return $request->validate([


            'program_code' => [
                'required',
                'string',
                'max:100',
            ],

            'phone_number' => [
                'required',
                'string',
                'max:20',
            ],

            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'gender' => [
                'nullable',
                Rule::in(['male', 'female', 'other']),
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],
            'status' => ['required', 'in:active,inactive'],



        ]);
    }

    /**
     * Normalize mobile.
     */
    private function normalizeMobile(?string $mobile): ?string
    {
        if (blank($mobile)) {
            return null;
        }

        $mobile = preg_replace('/\D+/', '', $mobile);

        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        return strlen($mobile) === 10
            ? $mobile
            : null;
    }
}
