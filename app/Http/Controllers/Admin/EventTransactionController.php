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
}
