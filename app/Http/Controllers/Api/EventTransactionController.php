<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectEventTransactionRequest;
use App\Http\Requests\StoreEventTransactionRequest;
use App\Http\Requests\UpdateEventTransactionRequest;
use App\Models\EventTransaction;
use App\Models\FieldAgent;
use App\Models\Partner;
use App\Models\PartnerPlacementUser;
use App\Services\EventTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\PartnerCenter;

class EventTransactionController extends Controller
{
    public function __construct(
        protected EventTransactionService $service
    ) {
    }

    /**
     * List transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

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

        /*
        |--------------------------------------------------------------------------
        | Role Based Filter
        |--------------------------------------------------------------------------
        */

        if ($user instanceof FieldAgent) {

            $query->where(
                'field_agent_id',
                $user->id
            );

        } elseif ($user instanceof Partner) {

            $query->where(
                'partner_id',
                $user->id
            );

        }elseif ($user instanceof PartnerCenter) {

            $query->where('partner_center_id', $user->id);

        }elseif ($user instanceof PartnerPlacementUser) {

            $query->where(
                'partner_placement_user_id',
                $user->id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

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
            ->paginate(
                min($request->integer('per_page', 20), 100)
            );

        return response()->json([
            'success' => true,
            'message' => 'Event transactions fetched successfully.',
            'data' => $transactions,
        ]);
    }

    /**
     * Show transaction.
     */
    public function show(
        Request $request,
        EventTransaction $eventTransaction
    ): JsonResponse {

        $this->authorize(
            'view',
            $eventTransaction
        );

        $eventTransaction->load([
            'learner',
            'partner',
            'partnerCenter',
            'partnerPlacementUser',
            'fieldAgent',
            'logs.user',
        ]);

        return response()->json([
            'success' => true,
            'data' => $eventTransaction,
        ]);
    }

    /**
     * Store.
     */
    public function store(
        StoreEventTransactionRequest $request
    ): JsonResponse {

        $this->authorize(
            'create',
            EventTransaction::class
        );

        $transaction = $this->service->create(
            $request->validated(),
            $request->user(),
            'frontend'
        );

        return response()->json([
            'success' => true,
            'message' => 'Event transaction created successfully.',
            'data' => $transaction,
        ], 201);
    }

    /**
     * Update.
     */
    public function update(
        UpdateEventTransactionRequest $request,
        EventTransaction $eventTransaction
    ): JsonResponse {

        $this->authorize(
            'update',
            $eventTransaction
        );

        $transaction = $this->service->update(
            $eventTransaction,
            $request->validated(),
            $request->user(),
            'frontend'
        );

        return response()->json([
            'success' => true,
            'message' => 'Event transaction updated successfully.',
            'data' => $transaction,
        ]);
    }

    /**
     * Submit.
     */
    public function submit(
        Request $request,
        EventTransaction $eventTransaction
    ): JsonResponse {

        $this->authorize(
            'submit',
            $eventTransaction
        );

        $transaction = $this->service->submit(
            $eventTransaction,
            $request->user(),
            'frontend'
        );

        return response()->json([
            'success' => true,
            'message' => 'Event transaction submitted successfully.',
            'data' => $transaction,
        ]);
    }

    /**
     * Under review.
     */
    public function underReview(
        Request $request,
        EventTransaction $eventTransaction
    ): JsonResponse {

        $this->authorize(
            'underReview',
            $eventTransaction
        );

        $transaction = $this->service->underReview(
            $eventTransaction,
            $request->user(),
            'backend'
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaction moved to under review.',
            'data' => $transaction,
        ]);
    }

    /**
     * Approve.
     */
    public function approve(
        Request $request,
        EventTransaction $eventTransaction
    ): JsonResponse {

        $this->authorize(
            'approve',
            $eventTransaction
        );

        $transaction = $this->service->approve(
            $eventTransaction,
            $request->user(),
            'backend'
        );

        return response()->json([
            'success' => true,
            'message' => 'Event transaction approved successfully.',
            'data' => $transaction,
        ]);
    }

    /**
     * Reject.
     */
    public function reject(
        RejectEventTransactionRequest $request,
        EventTransaction $eventTransaction
    ): JsonResponse {

        $this->authorize(
            'reject',
            $eventTransaction
        );

        $transaction = $this->service->reject(
            $eventTransaction,
            $request->comment,
            $request->user(),
            'backend'
        );

        return response()->json([
            'success' => true,
            'message' => 'Event transaction rejected.',
            'data' => $transaction,
        ]);
    }

    /**
     * Resubmit.
     */
    public function resubmit(
        Request $request,
        EventTransaction $eventTransaction
    ): JsonResponse {

        $this->authorize(
            'resubmit',
            $eventTransaction
        );

        $transaction = $this->service->resubmit(
            $eventTransaction,
            $request->user(),
            'frontend'
        );

        return response()->json([
            'success' => true,
            'message' => 'Event transaction resubmitted successfully.',
            'data' => $transaction,
        ]);
    }

    /**
     * Delete.
     */
    public function destroy(
        Request $request,
        EventTransaction $eventTransaction
    ): JsonResponse {

        $this->authorize(
            'delete',
            $eventTransaction
        );

        $this->service->delete(
            $eventTransaction,
            $request->user(),
            'frontend'
        );

        return response()->json([
            'success' => true,
            'message' => 'Event transaction deleted successfully.',
        ]);
    }


    public function authTest(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name ?? null,
                'class' => get_class($request->user()),
            ],
        ]);
    }


}
