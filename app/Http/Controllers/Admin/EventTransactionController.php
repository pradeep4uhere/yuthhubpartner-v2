<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectEventTransactionRequest;
use App\Http\Requests\StoreEventTransactionRequest;
use App\Http\Requests\UpdateEventTransactionRequest;
use App\Models\EventTransaction;
use App\Services\EventTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\PartnerPlacementUser;
use App\Models\FieldAgent;
use App\Models\EventMasterType;
use App\Models\EventCategoryMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EventTransactionController extends Controller
{
    public function __construct(
        protected EventTransactionService $service
    ) {
    }

    /**
     * Display a listing of event transactions.
     */

        public function index(Request $request): View
        {
            /*
            |--------------------------------------------------------------------------
            | Base query: all active transactions
            |--------------------------------------------------------------------------
            */
            $baseQuery = EventTransaction::query()->active();

            /*
            |--------------------------------------------------------------------------
            | Overall transaction count
            |--------------------------------------------------------------------------
            */
            $totalTransactions = (clone $baseQuery)->count();

            /*
            |--------------------------------------------------------------------------
            | Workflow status counts
            |--------------------------------------------------------------------------
            */
            $workflowCounts = [];

            foreach ([
                'draft',
                'submitted',
                'under_review',
                'approved',
                'rejected',
                'resubmitted',
            ] as $status) {
                $workflowCounts[$status] = (clone $baseQuery)
                    ->where('workflow_status', $status)
                    ->count();
            }

            $approvedTransactions = $workflowCounts['approved'];
            $submittedTransactions = $workflowCounts['submitted'];
            $underReviewTransactions = $workflowCounts['under_review'];
            $rejectedTransactions = $workflowCounts['rejected'];
            $resubmittedTransactions = $workflowCounts['resubmitted'];
            $draftTransactions = $workflowCounts['draft'];

            /*
            |--------------------------------------------------------------------------
            | Review status counts
            |--------------------------------------------------------------------------
            */
            $reviewCounts = [];

            foreach ([
                'under_review',
                'approved',
                'rejected',
                'pending',
                'return',
            ] as $status) {
                $reviewCounts[$status] = (clone $baseQuery)
                    ->where('review_status', $status)
                    ->count();
            }

            $openReviewTransactions = $reviewCounts['under_review'];
            $acceptedTransactions = $reviewCounts['approved'];
            $pendingReviewTransactions = $reviewCounts['pending'];
            $rejectedReviewTransactions = $reviewCounts['rejected'];
            $returnedTransactions = $reviewCounts['return'];

            /*
            |--------------------------------------------------------------------------
            | Today's transactions
            |--------------------------------------------------------------------------
            */
            $todayTransactions = (clone $baseQuery)
                ->whereDate('created_at', today())
                ->count();

            /*
            |--------------------------------------------------------------------------
            | Last 7 days trend (based on created_at)
            |--------------------------------------------------------------------------
            */
            $trendRows = (clone $baseQuery)
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->selectRaw('DATE(created_at) as transaction_date, COUNT(*) as total')
                ->groupBy('transaction_date')
                ->orderBy('transaction_date')
                ->get()
                ->keyBy('transaction_date');

            $transactionTrend = collect();

            for ($i = 6; $i >= 0; $i--) {
                $date = today()->subDays($i);
                $dateKey = $date->toDateString();

                $transactionTrend->push([
                    'date'  => $dateKey,
                    'label' => $date->format('d M'),
                    'total' => (int) ($trendRows->get($dateKey)->total ?? 0),
                ]);
            }

            $lastSevenDaysTransactions = $transactionTrend->sum('total');

            /*
            |--------------------------------------------------------------------------
            | Listing query with relationships
            |--------------------------------------------------------------------------
            */
            $query = (clone $baseQuery)->with([
                'learner',
                'partner',
                'partnerCenter',
                'partnerPlacementUser',
                'fieldAgent',
                'latestLog',
            ]);

            if ($request->filled('workflow_status')) {
                $query->where(
                    'workflow_status',
                    $request->input('workflow_status')
                );
            }

            if ($request->filled('review_status')) {
                $query->where(
                    'review_status',
                    $request->input('review_status')
                );
            }

            if ($request->filled('search')) {
                $search = trim($request->input('search'));

                $query->where(function ($q) use ($search) {
                    $q->where('beneficiary_name', 'like', "%{$search}%")
                        ->orWhere('beneficiary_phone_number', 'like', "%{$search}%")
                        ->orWhere('event_type_name', 'like', "%{$search}%")
                        ->orWhere('event_category_name', 'like', "%{$search}%");
                });
            }

            $transactions = $query
                ->latest()
                ->paginate(20)
                ->withQueryString();

            $filters = [
                'search' => $request->input('search', ''),
                'workflow_status' => $request->input('workflow_status', ''),
                'review_status' => $request->input('review_status', ''),
            ];

            return view('admin.event-transactions.index', compact(
                'transactions',
                'filters',
                'totalTransactions',
                'workflowCounts',
                'reviewCounts',
                'approvedTransactions',
                'submittedTransactions',
                'underReviewTransactions',
                'rejectedTransactions',
                'resubmittedTransactions',
                'draftTransactions',
                'openReviewTransactions',
                'acceptedTransactions',
                'pendingReviewTransactions',
                'rejectedReviewTransactions',
                'returnedTransactions',
                'todayTransactions',
                'transactionTrend',
                'lastSevenDaysTransactions'
            ));
        }




        /**
         * Return paginated event transactions and filtered summary counts for the React directory.
         */
        public function data(Request $request): JsonResponse
        {
            $validated = $request->validate([
                'search' => ['nullable', 'string', 'max:150'],
                'beneficiary_name' => ['nullable', 'string', 'max:150'],
                'mobile' => ['nullable', 'string', 'max:30'],
                'event_type_id' => ['nullable', 'string', 'max:50'],
                'event_category' => ['nullable', 'string', 'max:150'],
                'partner_id' => ['nullable', 'integer'],
                'partner_center_id' => ['nullable', 'integer'],
                'placement_partner_id' => ['nullable', 'integer'],
                'field_agent_id' => ['nullable', 'integer'],
                'workflow_status' => ['nullable', 'string', 'max:50'],
                'review_status' => ['nullable', 'string', 'max:50'],
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
                'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ]);

            $query = EventTransaction::query()
                ->active()
                ->with([
                    'partner:id,name',
                    'partnerCenter:id,name',
                    'partnerPlacementUser:id,name',
                    'fieldAgent:id,name,sakhi_id',
                ]);

            $this->applyAdvancedFilters($query, $validated);

            // Calculate summary counts using all filtered records, before pagination.
            $summaryQuery = clone $query;

            $summary = [
                'total' => (clone $summaryQuery)->count(),

                'approved' => (clone $summaryQuery)
                    ->where('review_status', 'accepted')
                    ->count(),

                'pending_review' => (clone $summaryQuery)
                    ->whereIn('review_status', ['open', 'pending', 'returned'])
                    ->count(),

                'rejected' => (clone $summaryQuery)
                    ->where('review_status', 'rejected')
                    ->count(),
            ];

            $transactions = $query
                ->latest('id')
                ->paginate($validated['per_page'] ?? 20)
                ->withQueryString();

            $rows = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'beneficiary_name' => $transaction->beneficiary_name,
                    'beneficiary_phone_number' => $transaction->beneficiary_phone_number,
                    'event_type_name' => $transaction->event_type_name,
                    'event_category_name' => $transaction->event_category_name,
                    'partner_name' => $transaction->partner?->name,
                    'partner_center_name' => $transaction->partnerCenter?->name,
                    'placement_partner_name' => $transaction->partnerPlacementUser?->name,
                    'field_agent_name' => $transaction->fieldAgent?->name,
                    'field_agent_sakhi_id' => $transaction->fieldAgent?->sakhi_id,
                    'event_create_date' => $transaction->event_create_date?->format('d M Y'),
                    'event_value' => $transaction->event_value,
                    'workflow_status' => $transaction->workflow_status,
                    'review_status' => $transaction->review_status,
                    'urls' => [
                        'show' => route('admin.event-transactions.show', $transaction),
                        'edit' => route('admin.event-transactions.edit', $transaction),
                        'delete' => route('admin.event-transactions.destroy', $transaction),
                    ],
                    'permissions' => [
                        'update' => Gate::allows('update', $transaction),
                        'delete' => Gate::allows('delete', $transaction),
                    ],
                ];
            });

            return response()->json([
                'data' => $rows,
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ],
                'summary' => $summary,
            ]);
        }
            /**
             * Apply all supplied advanced filters to the transaction query.
             */
            private function applyAdvancedFilters($query, array $filters): void
            {
                if (!empty($filters['search'])) {
                    $search = trim($filters['search']);

                    $query->where(function ($builder) use ($search) {
                        $builder->where('beneficiary_name', 'like', "%{$search}%")
                            ->orWhere('beneficiary_phone_number', 'like', "%{$search}%")
                            ->orWhere('event_type_name', 'like', "%{$search}%")
                            ->orWhere('event_category_name', 'like', "%{$search}%");
                    });
                }

                if (!empty($filters['beneficiary_name'])) {
                    $query->where(
                        'beneficiary_name',
                        'like',
                        '%' . trim($filters['beneficiary_name']) . '%'
                    );
                }

                if (!empty($filters['mobile'])) {
                    $query->where(
                        'beneficiary_phone_number',
                        'like',
                        '%' . trim($filters['mobile']) . '%'
                    );
                }

                if (!empty($filters['event_type_id'])) {
                    $query->where('event_master_type_id', $filters['event_type_id']);
                }

                if (!empty($filters['event_category'])) {
                    $query->where('event_category_name', $filters['event_category']);
                }

                foreach ([
                    'partner_id' => 'partner_id',
                    'partner_center_id' => 'partner_center_id',
                    'placement_partner_id' => 'partner_placement_user_id',
                    'field_agent_id' => 'field_agent_id',
                ] as $filter => $column) {
                    if (!empty($filters[$filter])) {
                        $query->where($column, $filters[$filter]);
                    }
                }

                if (!empty($filters['workflow_status'])) {
                    $query->where('workflow_status', $filters['workflow_status']);
                }

                if (!empty($filters['review_status'])) {
                    $query->where('review_status', $filters['review_status']);
                }

                if (!empty($filters['date_from'])) {
                    $query->whereDate('event_create_date', '>=', $filters['date_from']);
                }

                if (!empty($filters['date_to'])) {
                    $query->whereDate('event_create_date', '<=', $filters['date_to']);
                }
            }

            /**
             * Return available dropdown values for the advanced search filters.
             */
            public function filterOptions(): JsonResponse
            {
                return response()->json([
                    'partners' => Partner::query()
                        ->orderBy('name')
                        ->get(['id', 'name']),

                    'partner_centers' => PartnerCenter::query()
                        ->orderBy('name')
                        ->get(['id', 'name']),

                    'placement_partners' => PartnerPlacementUser::query()
                        ->orderBy('name')
                        ->get(['id', 'name']),

                    'field_agents' => FieldAgent::query()
                        ->orderBy('name')
                        ->get(['id', 'name']),

                    'event_types' => EventMasterType::query()
                        ->orderBy('name')
                        ->get(['id', 'name']),

                    'event_categories' => EventCategoryMaster::query()
                        ->orderBy('name')
                        ->get(['id', 'name'])
                        ->pluck('name')
                        ->unique()
                        ->values(),

                    'workflow_statuses' => [
                        ['value' => 'draft', 'label' => 'Draft'],
                        ['value' => 'submitted', 'label' => 'Submitted'],
                        ['value' => 'under_review', 'label' => 'Under Review'],
                        ['value' => 'approved', 'label' => 'Approved'],
                        ['value' => 'rejected', 'label' => 'Rejected'],
                        ['value' => 'resubmitted', 'label' => 'Resubmitted'],
                    ],

                    'review_statuses' => [
                        ['value' => 'under_review', 'label' => 'Under Review'],
                        ['value' => 'approved', 'label' => 'Approved'],
                        ['value' => 'accepted', 'label' => 'Accepted'],
                        ['value' => 'rejected', 'label' => 'Rejected'],
                        ['value' => 'pending', 'label' => 'Pending'],
                        ['value' => 'return', 'label' => 'Returned'],
                        ['value' => 'open', 'label' => 'Open'],
                    ],
                ]);
            }


    /**
     * Show create form.
     */
    public function create(): View
    {
        Gate::authorize(
            'create',
            EventTransaction::class
        );

        return view(
            'admin.event-transactions.create'
        );
    }

    /**
     * Store transaction.
     */
    public function store(
        StoreEventTransactionRequest $request
    ): RedirectResponse {

        Gate::authorize(
            'create',
            EventTransaction::class
        );

        $this->service->create(
            $request->validated(),
            $request->user(),
            'backend'
        );

        return redirect()
            ->route('admin.event-transactions.index')
            ->with(
                'success',
                'Event transaction created successfully.'
            );
    }

    /**
     * Display transaction.
     */
    public function show(
        EventTransaction $eventTransaction
    ): View {

        Gate::authorize('view', $eventTransaction);

        $fieldTypes = DB::table('field_types')->get();
        $industryTypes = DB::table('industry_types')->get();

        $eventTransaction->load([
            'learner',
            'partner',
            'partnerCenter',
            'partnerPlacementUser',
            'fieldAgent',
            'logs.user',
        ]);

       // dd($eventTransaction);


        return view(
            'admin.event-transactions.show',
            compact('eventTransaction','fieldTypes','industryTypes')
        );
    }

    /**
     * Show edit form.
     */
    public function edit(
        EventTransaction $eventTransaction
    ): View {

        Gate::authorize(
            'update',
            $eventTransaction
        );

        return view(
            'admin.event-transactions.edit',
            compact('eventTransaction')
        );
    }

    /**
     * Update transaction.
     */
    public function update(
        UpdateEventTransactionRequest $request,
        EventTransaction $eventTransaction
    ): RedirectResponse {



        Gate::authorize(
            'update',
            $eventTransaction
        );

        //dd('Policy passed successfully');
        //dd($request->all());


        $this->service->update(
            $eventTransaction,
            $request->validated(),
            $request->user(),
            'backend'
        );

        return redirect()
            ->route(
                'admin.event-transactions.show',
                $eventTransaction
            )
            ->with(
                'success',
                'Event transaction updated successfully.'
            );
    }

    /**
     * Delete transaction.
     */
    public function destroy(
        Request $request,
        EventTransaction $eventTransaction
    ): RedirectResponse {

        Gate::authorize(
            'delete',
            $eventTransaction
        );

        $this->service->delete(
            $eventTransaction,
            $request->user(),
            'backend'
        );

        return redirect()
            ->route(
                'admin.event-transactions.index'
            )
            ->with(
                'success',
                'Event transaction deleted successfully.'
            );
    }

    /**
     * Move transaction to under review.
     */
    public function underReview(
        Request $request,
        EventTransaction $eventTransaction
    ): RedirectResponse {

        Gate::authorize(
            'underReview',
            $eventTransaction
        );

        $this->service->underReview(
            $eventTransaction,
            $request->user(),
            'backend'
        );

        return back()->with(
            'success',
            'Transaction moved to under review.'
        );
    }

    /**
     * Approve transaction.
     */
    public function approve(
        Request $request,
        EventTransaction $eventTransaction
    ): RedirectResponse {

        Gate::authorize(
            'approve',
            $eventTransaction
        );

        $this->service->approve(
            $eventTransaction,
            $request->user(),
            'backend'
        );

        return back()->with(
            'success',
            'Event transaction approved successfully.'
        );
    }

    /**
     * Reject transaction.
     */
    public function reject(
        RejectEventTransactionRequest $request,
        EventTransaction $eventTransaction
    ): RedirectResponse {

        Gate::authorize(
            'reject',
            $eventTransaction
        );

        $this->service->reject(
            $eventTransaction,
            $request->comment,
            $request->user(),
            'backend'
        );

        return back()->with(
            'success',
            'Event transaction rejected.'
        );
    }





    /**
     * Get daily event transaction counts for the last seven days.
     */
    public function transactionActivity()
    {
        $startDate = Carbon::today()->subDays(6);
        $endDate = Carbon::today();

        $records = EventTransaction::query()
            ->select(
                DB::raw('DATE(created_at) as transaction_date'),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('transaction_date')
            ->get()
            ->keyBy('transaction_date');

        $data = collect();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $count = (int) ($records->get($key)->total ?? 0);

            $data->push([
                'date' => $key,
                'label' => $date->format('d M'),
                'count' => $count,
            ]);
        }

        return response()->json([
            'total' => $data->sum('count'),
            'data' => $data,
        ]);
    }



}
