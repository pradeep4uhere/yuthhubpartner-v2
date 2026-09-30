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
        $query = EventTransaction::query()
            ->with([
                'learner',
                'partner',
                'partnerCenter',
                'partnerPlacementUser',
                'fieldAgent',
                'latestLog',
            ])
            ->active();

        if ($request->filled('workflow_status')) {
            $query->where(
                'workflow_status',
                $request->workflow_status
            );
        }

        if ($request->filled('review_status')) {
            $query->where(
                'review_status',
                $request->review_status
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'beneficiary_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'beneficiary_phone_number',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'event_type_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'event_category_name',
                    'like',
                    "%{$search}%"
                );
            });
        }

        $transactions = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view(
            'admin.event-transactions.index',
            compact('transactions')
        );
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $this->authorize(
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

        $this->authorize(
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

        $eventTransaction->load([
            'learner',
            'partner',
            'partnerCenter',
            'partnerPlacementUser',
            'fieldAgent',
            'logs.user',
        ]);

        return view(
            'admin.event-transactions.show',
            compact('eventTransaction')
        );
    }

    /**
     * Show edit form.
     */
    public function edit(
        EventTransaction $eventTransaction
    ): View {

        $this->authorize(
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

        $this->authorize(
            'update',
            $eventTransaction
        );

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

        $this->authorize(
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

        $this->authorize(
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

        $this->authorize(
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

        $this->authorize(
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