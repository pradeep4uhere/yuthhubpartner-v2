<?php

namespace App\Services;

use App\Models\EventTransaction;
use App\Models\EventTransactionLog;
use App\Support\EventTransactionStatus;
use App\Support\EventTransactionReviewStatus;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EventTransactionService
{
    /**
     * Create transaction as Draft.
     */
    public function create(
        array $data,
        ?Authenticatable $user = null,
        string $actorType = 'frontend'
    ): EventTransaction {
        return DB::transaction(function () use ($data, $user, $actorType) {
            $data['workflow_status'] = EventTransactionStatus::DRAFT;
            $data['review_status'] = EventTransactionReviewStatus::OPEN;
            $data['status'] = true;

            $transaction = EventTransaction::create($data);

            $this->createLog(
                transaction: $transaction,
                action: 'created',
                oldStatus: null,
                newStatus: EventTransactionStatus::DRAFT,
                user: $user,
                actorType: $actorType,
                comment: 'Event transaction created.',
                newValues: [
                    'workflow_status' => EventTransactionStatus::DRAFT,
                    'review_status' => EventTransactionReviewStatus::OPEN,
                ]
            );

            return $transaction->fresh();
        });
    }

        /**
         * Update transaction fields and optionally process an Admin status change.
         */
        public function update(
            EventTransaction $transaction,
            array $data,
            ?Authenticatable $user = null,
            string $actorType = 'frontend'
        ): EventTransaction {
            return DB::transaction(function () use (
                $transaction,
                $data,
                $user,
                $actorType
            ) {
                // Keep the current transaction fresh inside the transaction.
                $transaction = EventTransaction::whereKey($transaction->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $requestedReviewStatus = $data['review_status'] ?? null;
                $requestedWorkflowStatus = $data['workflow_status'] ?? null;

                $comment = $data['comment'] ?? null;

                unset(
                    $data['review_status'],
                    $data['workflow_status'],
                    $data['comment']
                );

                // Capture old field values before changing anything.
                $oldValues = $transaction->only(array_keys($data));
                $oldWorkflowStatus = $transaction->workflow_status;
                $oldReviewStatus = $transaction->review_status;

                // Map form fields to database columns.
                if (array_key_exists('field_type', $data)) {
                    $data['field_type_id'] = $data['field_type'];
                    unset($data['field_type']);
                }

                if (array_key_exists('industry_type', $data)) {
                    $data['industry_type_id'] = $data['industry_type'];
                    unset($data['industry_type']);
                }

                // Update ordinary fields first.
                if (!empty($data)) {
                    $transaction->update($data);
                }

                // Process a requested status through the dedicated workflow methods.
                if ($requestedReviewStatus !== null) {
                    switch ($requestedReviewStatus) {
                        case 'Open':
                            $transaction = $this->underReview(
                                $transaction,
                                $user,
                                $actorType
                            );
                            break;

                        case 'Accepted':
                            $transaction = $this->approve(
                                $transaction,
                                $user,
                                $actorType
                            );
                            break;

                        case 'Rejected':
                            $transaction = $this->reject(
                                $transaction,
                                $comment,
                                $user,
                                $actorType
                            );
                            break;

                        case 'Return':
                            $transaction = $this->returnForCorrection(
                                $transaction,
                                $comment,
                                $user,
                                $actorType
                            );
                            break;

                       case 'Pending':
                                // Approved/reviewed transactions should reopen for review.
                                $targetWorkflowStatus = in_array(
                                    strtolower(trim((string) $transaction->workflow_status)),
                                    [
                                        EventTransactionStatus::APPROVED,
                                        EventTransactionStatus::RETURNED,
                                        EventTransactionStatus::REJECTED,
                                        EventTransactionStatus::UNDER_REVIEW,
                                    ],
                                    true
                                )
                                    ? EventTransactionStatus::UNDER_REVIEW
                                    : EventTransactionStatus::SUBMITTED;

                                $transaction = $this->changeStatus(
                                    transaction: $transaction,
                                    newWorkflowStatus: $targetWorkflowStatus,
                                    newReviewStatus: EventTransactionReviewStatus::PENDING,
                                    action: 'status_updated',
                                    user: $user,
                                    actorType: $actorType,
                                    comment: $comment
                                );
                                break;

                        default:
                            throw new RuntimeException(
                                'Invalid review status: ' . $requestedReviewStatus
                            );
                    }
                } elseif (
                    $requestedWorkflowStatus !== null &&
                    $requestedWorkflowStatus !== $oldWorkflowStatus
                ) {
                    throw new RuntimeException(
                        'Workflow status must be changed through a dedicated workflow action.'
                    );
                }

                $transaction = $transaction->fresh();

                // Log ordinary field changes. Workflow methods log their own status changes.
                if (!empty($data)) {
                    $this->createLog(
                        transaction: $transaction,
                        action: 'updated',
                        oldStatus: $oldWorkflowStatus,
                        newStatus: $transaction->workflow_status,
                        user: $user,
                        actorType: $actorType,
                        comment: $comment,
                        oldValues: array_merge($oldValues, [
                            'workflow_status' => $oldWorkflowStatus,
                            'review_status' => $oldReviewStatus,
                        ]),
                        newValues: array_merge(
                            $transaction->only(array_keys($data)),
                            [
                                'workflow_status' => $transaction->workflow_status,
                                'review_status' => $transaction->review_status,
                            ]
                        )
                    );
                }

                return $transaction->fresh();
            });
        }

    /**
     * Submit Draft transaction.
     */
    public function submit(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'frontend'
    ): EventTransaction {
        return $this->changeStatus(
            transaction: $transaction,
            newWorkflowStatus: EventTransactionStatus::SUBMITTED,
            newReviewStatus: EventTransactionReviewStatus::PENDING,
            action: 'submitted',
            user: $user,
            actorType: $actorType,
            comment: 'Event transaction submitted for review.',
            extraData: [
                'event_date_submitted' => now()->toDateString(),
            ]
        );
    }

    /**
     * Start or restart Admin review.
     */
    public function underReview(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'backend'
    ): EventTransaction {
        return $this->changeStatus(
            transaction: $transaction,
            newWorkflowStatus: EventTransactionStatus::UNDER_REVIEW,
            newReviewStatus: EventTransactionReviewStatus::OPEN,
            action: 'under_review',
            user: $user,
            actorType: $actorType,
            comment: 'Transaction moved to under review.'
        );
    }

    /**
     * Approve transaction.
     */
    public function approve(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'backend'
    ): EventTransaction {
        return $this->changeStatus(
            transaction: $transaction,
            newWorkflowStatus: EventTransactionStatus::APPROVED,
            newReviewStatus: EventTransactionReviewStatus::ACCEPTED,
            action: 'approved',
            user: $user,
            actorType: $actorType,
            comment: 'Event transaction approved.'
        );
    }

    /**
     * Return transaction for correction.
     */
    public function returnForCorrection(
        EventTransaction $transaction,
        string $comment,
        ?Authenticatable $user = null,
        string $actorType = 'backend'
    ): EventTransaction {
        if (trim($comment) === '') {
            throw new RuntimeException(
                'A return reason is required.'
            );
        }

        return $this->changeStatus(
            transaction: $transaction,
            newWorkflowStatus: EventTransactionStatus::RETURNED,
            newReviewStatus: EventTransactionReviewStatus::RETURN,
            action: 'returned',
            user: $user,
            actorType: $actorType,
            comment: $comment,
            extraData: ['last_comment' => $comment]
        );
    }

    /**
     * Reject transaction.
     * Field Agent can resubmit it later.
     */
    public function reject(
        EventTransaction $transaction,
        string $comment,
        ?Authenticatable $user = null,
        string $actorType = 'backend'
    ): EventTransaction {
        if (trim($comment) === '') {
            throw new RuntimeException(
                'A rejection reason is required.'
            );
        }

        return $this->changeStatus(
            transaction: $transaction,
            newWorkflowStatus: EventTransactionStatus::REJECTED,
            newReviewStatus: EventTransactionReviewStatus::REJECTED,
            action: 'rejected',
            user: $user,
            actorType: $actorType,
            comment: $comment,
            extraData: ['last_comment' => $comment]
        );
    }

    /**
     * Resubmit Returned or Rejected transaction.
     */
    public function resubmit(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'frontend'
    ): EventTransaction {
        if (!in_array($transaction->workflow_status, [
            EventTransactionStatus::RETURNED,
            EventTransactionStatus::REJECTED,
        ], true)) {
            throw new RuntimeException(
                'Only returned or rejected transactions can be resubmitted.'
            );
        }

        return $this->changeStatus(
            transaction: $transaction,
            newWorkflowStatus: EventTransactionStatus::RESUBMITTED,
            newReviewStatus: EventTransactionReviewStatus::PENDING,
            action: 'resubmitted',
            user: $user,
            actorType: $actorType,
            comment: 'Transaction resubmitted after correction.'
        );
    }

    /**
     * Centralized workflow + review status update and audit log.
     */
    protected function changeStatus(
        EventTransaction $transaction,
        string $newWorkflowStatus,
        string $newReviewStatus,
        string $action,
        ?Authenticatable $user,
        string $actorType,
        ?string $comment = null,
        array $extraData = []
    ): EventTransaction {
        return DB::transaction(function () use (
            $transaction,
            $newWorkflowStatus,
            $newReviewStatus,
            $action,
            $user,
            $actorType,
            $comment,
            $extraData
        ) {
            $transaction = EventTransaction::query()
                ->whereKey($transaction->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $oldWorkflowStatus = $transaction->workflow_status;
            $oldReviewStatus = $transaction->review_status;

            logger()->info('Event Transaction Workflow Debug', [
                    'old_workflow_status' => $oldWorkflowStatus,
                    'new_workflow_status' => $newWorkflowStatus,
                    'allowed_transitions' => EventTransactionStatus::transitions()[
                        $oldWorkflowStatus
                    ] ?? [],
                ]);

            if (!EventTransactionStatus::canTransition(
                $oldWorkflowStatus,
                $newWorkflowStatus
            )) {
                throw new RuntimeException(
                    'Invalid workflow transition: '
                    . EventTransactionStatus::label($oldWorkflowStatus)
                    . ' → '
                    . EventTransactionStatus::label($newWorkflowStatus)
                );
            }

            $transaction->update(array_merge($extraData, [
                'workflow_status' => $newWorkflowStatus,
                'review_status' => $newReviewStatus,
            ]));

            $this->createLog(
                transaction: $transaction,
                action: $action,
                oldStatus: $oldWorkflowStatus,
                newStatus: $newWorkflowStatus,
                user: $user,
                actorType: $actorType,
                comment: $comment,
                oldValues: [
                    'workflow_status' => $oldWorkflowStatus,
                    'review_status' => $oldReviewStatus,
                ],
                newValues: [
                    'workflow_status' => $newWorkflowStatus,
                    'review_status' => $newReviewStatus,
                ]
            );

            return $transaction->fresh();
        });
    }

    /**
     * Delete transaction and record the action.
     */
    public function delete(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'frontend'
    ): bool {
        return DB::transaction(function () use (
            $transaction,
            $user,
            $actorType
        ) {
            $this->createLog(
                transaction: $transaction,
                action: 'deleted',
                oldStatus: $transaction->workflow_status,
                newStatus: null,
                user: $user,
                actorType: $actorType,
                comment: 'Event transaction deleted.',
                oldValues: [
                    'workflow_status' => $transaction->workflow_status,
                    'review_status' => $transaction->review_status,
                ]
            );

            return (bool) $transaction->delete();
        });
    }

    /**
     * Write to the existing EventTransactionLog table.
     */
    protected function createLog(
        EventTransaction $transaction,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?Authenticatable $user,
        string $actorType,
        ?string $actorName = null,
        ?string $comment = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): EventTransactionLog {
        if (!$actorName && $user) {
            $name = data_get($user, 'name');

            if (is_string($name) && trim($name) !== '') {
                $actorName = $name;
            } else {
                $userId = $user->getAuthIdentifier();

                $actorName = match (class_basename($user)) {
                    'Admin' => DB::table('admin')
                        ->where('id', $userId)
                        ->value('name'),

                    'FieldAgent' => DB::table('field_agent')
                        ->where('id', $userId)
                        ->value('name'),

                    default => null,
                };
            }
        }

        return EventTransactionLog::create([
            'event_transaction_id' => $transaction->id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'actor_type' => $actorType,
            'actor_name' => $actorName,
            'user_id' => $user?->getAuthIdentifier(),
            'user_type' => $this->getUserType($user),
            'comment' => $comment,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Resolve authenticated user type.
     */
    protected function getUserType(
        ?Authenticatable $user
    ): ?string {
        if (!$user) {
            return null;
        }

        return match (class_basename($user)) {
            'Admin' => 'Admin',
            'FieldAgent' => 'FieldAgent',
            'Partner' => 'Partner',
            'PartnerPlacementUser' => 'PartnerPlacementUser',
            default => class_basename($user),
        };
    }
}

