<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\EventTransaction;
use App\Models\FieldAgent;
use App\Models\Partner;
use App\Models\PartnerPlacementUser;
use App\Models\PartnerCenter;

class EventTransactionPolicy
{
    /**
     * View transaction listing.
     */
    public function viewAny($user): bool
    {
        return $user instanceof Admin
            || $user instanceof FieldAgent
            || $user instanceof Partner
            || $user instanceof PartnerCenter
            || $user instanceof PartnerPlacementUser;
    }

    /**
     * View single transaction.
     */
    public function view(
        $user,
        EventTransaction $transaction
    ): bool {

        if ($user instanceof Admin) {
            return true;
        }

        if ($user instanceof FieldAgent) {
            return (int) $transaction->field_agent_id === (int) $user->id;
        }

        if ($user instanceof Partner) {
            return (int) $transaction->partner_id === (int) $user->id;
        }

        if ($user instanceof PartnerPlacementUser) {
            return (int) $transaction->partner_placement_user_id === (int) $user->id;
        }

        if ($user instanceof PartnerCenter) {
            return (int) $transaction->partner_center_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Create transaction.
     */
    public function create($user): bool
    {
        return $user instanceof Admin
            || $user instanceof FieldAgent
            || $user instanceof Partner
            || $user instanceof PartnerCenter
            || $user instanceof PartnerPlacementUser;
    }

    /**
     * Update transaction.
     */
    public function update(
        $user,
        EventTransaction $transaction
    ): bool {

        if ($user instanceof Admin) {
            return true;
        }

        if (!in_array(
            $transaction->workflow_status,
            ['draft', 'rejected']
        )) {
            return false;
        }

        return $this->belongsToUser($user, $transaction);
    }

    /**
     * Submit transaction.
     */
    public function submit(
        $user,
        EventTransaction $transaction
    ): bool {

        if ($transaction->workflow_status !== 'draft') {
            return false;
        }

        return $user instanceof Admin
            || $this->belongsToUser($user, $transaction);
    }

    /**
     * Under review.
     */
    public function underReview(
        $user,
        EventTransaction $transaction
    ): bool {

        return $user instanceof Admin
            && in_array(
                $transaction->workflow_status,
                ['submitted', 'resubmitted']
            );
    }

    /**
     * Approve.
     */
    public function approve(
        $user,
        EventTransaction $transaction
    ): bool {

        return $user instanceof Admin
            && in_array(
                $transaction->workflow_status,
                ['submitted', 'under_review', 'resubmitted']
            );
    }

    /**
     * Reject.
     */
    public function reject(
        $user,
        EventTransaction $transaction
    ): bool {

        return $user instanceof Admin
            && in_array(
                $transaction->workflow_status,
                ['submitted', 'under_review', 'resubmitted']
            );
    }

    /**
     * Resubmit.
     */
    public function resubmit(
        $user,
        EventTransaction $transaction
    ): bool {

        if ($transaction->workflow_status !== 'rejected') {
            return false;
        }

        return $user instanceof Admin
            || $this->belongsToUser($user, $transaction);
    }

    /**
     * Delete.
     */
    public function delete(
        $user,
        EventTransaction $transaction
    ): bool {

        if ($user instanceof Admin) {
            return true;
        }

        if ($transaction->workflow_status !== 'draft') {
            return false;
        }

        return $this->belongsToUser($user, $transaction);
    }

    /**
     * Check ownership.
     */
    protected function belongsToUser(
        $user,
        EventTransaction $transaction
    ): bool {

        if ($user instanceof FieldAgent) {
            return (int) $transaction->field_agent_id === (int) $user->id;
        }

        if ($user instanceof Partner) {
            return (int) $transaction->partner_id === (int) $user->id;
        }

        if ($user instanceof PartnerPlacementUser) {
            return (int) $transaction->partner_placement_user_id === (int) $user->id;
        }

        if ($user instanceof PartnerCenter) {
            return (int) $transaction->partner_center_id === (int) $user->id;
        }

        return false;
    }
}
