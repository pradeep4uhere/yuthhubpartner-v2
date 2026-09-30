<?php

namespace App\Services;

use App\Models\EventTransaction;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use App\Models\EventTransactionLog;

class EventTransactionService
{
    /**
     * Create transaction as draft.
     */
    public function create(
        array $data,
        ?Authenticatable $user = null,
        string $actorType = 'frontend'
    ): EventTransaction {
        return DB::transaction(function () use ($data, $user, $actorType) {

            $data['workflow_status'] = 'draft';
            $data['status'] = true;

            $transaction = EventTransaction::create($data);

            $this->createLog(
                transaction: $transaction,
                action: 'created',
                oldStatus: null,
                newStatus: 'draft',
                user: $user,
                actorType: $actorType,
                comment: 'Event transaction created.'
            );

            return $transaction->fresh();
        });
    }

    /**
     * Update transaction.
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

            $oldValues = $transaction->only(array_keys($data));
            $oldStatus = $transaction->workflow_status;

            $transaction->update($data);

            $this->createLog(
                transaction: $transaction,
                action: 'updated',
                oldStatus: $oldStatus,
                newStatus: $transaction->workflow_status,
                user: $user,
                actorType: $actorType,
                comment: 'Event transaction updated.',
                oldValues: $oldValues,
                newValues: $transaction->only(array_keys($data))
            );

            return $transaction->fresh();
        });
    }

    /**
     * Submit transaction.
     */
    public function submit(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'frontend'
    ): EventTransaction {

        if ($transaction->workflow_status !== 'draft') {
            throw new \RuntimeException(
                'Only draft transactions can be submitted.'
            );
        }

        return DB::transaction(function () use (
            $transaction,
            $user,
            $actorType
        ) {

            $oldStatus = $transaction->workflow_status;

            $transaction->update([
                'workflow_status' => 'submitted',
                'event_date_submitted' => now()->toDateString(),
            ]);

            $this->createLog(
                transaction: $transaction,
                action: 'submitted',
                oldStatus: $oldStatus,
                newStatus: 'submitted',
                user: $user,
                actorType: $actorType,
                comment: 'Event transaction submitted for review.'
            );

            return $transaction->fresh();
        });
    }

    /**
     * Move transaction to under review.
     */
    public function underReview(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'backend'
    ): EventTransaction {

        if (!in_array(
            $transaction->workflow_status,
            ['submitted', 'resubmitted']
        )) {
            throw new \RuntimeException(
                'Transaction cannot be moved to under review.'
            );
        }

        return DB::transaction(function () use (
            $transaction,
            $user,
            $actorType
        ) {

            $oldStatus = $transaction->workflow_status;

            $transaction->update([
                'workflow_status' => 'under_review',
                'review_status' => 'under_review',
            ]);

            $this->createLog(
                transaction: $transaction,
                action: 'under_review',
                oldStatus: $oldStatus,
                newStatus: 'under_review',
                user: $user,
                actorType: $actorType,
                comment: 'Transaction moved to under review.'
            );

            return $transaction->fresh();
        });
    }

    /**
     * Approve transaction.
     */
    public function approve(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'backend'
    ): EventTransaction {

        if (!in_array(
            $transaction->workflow_status,
            ['submitted', 'under_review', 'resubmitted']
        )) {
            throw new \RuntimeException(
                'Transaction cannot be approved.'
            );
        }

        return DB::transaction(function () use (
            $transaction,
            $user,
            $actorType
        ) {

            $oldStatus = $transaction->workflow_status;

            $transaction->update([
                'workflow_status' => 'approved',
                'review_status' => 'approved',
            ]);

            $this->createLog(
                transaction: $transaction,
                action: 'approved',
                oldStatus: $oldStatus,
                newStatus: 'approved',
                user: $user,
                actorType: $actorType,
                comment: 'Event transaction approved.'
            );

            return $transaction->fresh();
        });
    }

    /**
     * Reject transaction.
     */
    public function reject(
        EventTransaction $transaction,
        string $comment,
        ?Authenticatable $user = null,
        string $actorType = 'backend'
    ): EventTransaction {

        if (!in_array(
            $transaction->workflow_status,
            ['submitted', 'under_review', 'resubmitted']
        )) {
            throw new \RuntimeException(
                'Transaction cannot be rejected.'
            );
        }

        return DB::transaction(function () use (
            $transaction,
            $comment,
            $user,
            $actorType
        ) {

            $oldStatus = $transaction->workflow_status;

            $transaction->update([
                'workflow_status' => 'rejected',
                'review_status' => 'rejected',
                'last_comment' => $comment,
            ]);

            $this->createLog(
                transaction: $transaction,
                action: 'rejected',
                oldStatus: $oldStatus,
                newStatus: 'rejected',
                user: $user,
                actorType: $actorType,
                comment: $comment
            );

            return $transaction->fresh();
        });
    }

    /**
     * Resubmit rejected transaction.
     */
    public function resubmit(
        EventTransaction $transaction,
        ?Authenticatable $user = null,
        string $actorType = 'frontend'
    ): EventTransaction {

        if ($transaction->workflow_status !== 'rejected') {
            throw new \RuntimeException(
                'Only rejected transactions can be resubmitted.'
            );
        }

        return DB::transaction(function () use (
            $transaction,
            $user,
            $actorType
        ) {

            $oldStatus = $transaction->workflow_status;

            $transaction->update([
                'workflow_status' => 'resubmitted',
                'review_status' => 'resubmitted',
            ]);

            $this->createLog(
                transaction: $transaction,
                action: 'resubmitted',
                oldStatus: $oldStatus,
                newStatus: 'resubmitted',
                user: $user,
                actorType: $actorType,
                comment: 'Transaction resubmitted after rejection.'
            );

            return $transaction->fresh();
        });
    }

    /**
     * Delete transaction.
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
                comment: 'Event transaction deleted.'
            );

            return $transaction->delete();
        });
    }

    /**
     * Create transaction log.
     */
    protected function createLog(
        EventTransaction $transaction,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?Authenticatable $user,
        string $actorType,
        ?string $comment = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): EventTransactionLog {

        return EventTransactionLog::create([
            'event_transaction_id' => $transaction->id,

            'action' => $action,

            'old_status' => $oldStatus,
            'new_status' => $newStatus,

            'actor_type' => $actorType,

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
     * Get actor model type.
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
