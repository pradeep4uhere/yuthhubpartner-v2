<?php

namespace App\Support;

class EventTransactionStatus
{
    /*
    |--------------------------------------------------------------------------
    | Status Definitions
    |--------------------------------------------------------------------------
    | Add or remove statuses here.
    */

    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const UNDER_REVIEW = 'under_review';
    public const RETURNED = 'returned';
    public const REJECTED = 'rejected';
    public const RESUBMITTED = 'resubmitted';
    public const APPROVED = 'approved';

    /*
    |--------------------------------------------------------------------------
    | All Statuses and Labels
    |--------------------------------------------------------------------------
    */

    public static function labels(): array
    {
        return [
            self::DRAFT        => 'Draft',
            self::SUBMITTED    => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::RETURNED     => 'Returned',
            self::REJECTED     => 'Rejected',
            self::RESUBMITTED  => 'Resubmitted',
            self::APPROVED     => 'Approved',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Dropdown Options
    |--------------------------------------------------------------------------
    */

    public static function options(): array
    {
        return self::labels();
    }

    /*
    |--------------------------------------------------------------------------
    | Single Status Label
    |--------------------------------------------------------------------------
    */

    public static function label(?string $status): string
    {
        if (!$status) {
            return 'Unknown';
        }

        return self::labels()[$status]
            ?? ucfirst(str_replace('_', ' ', $status));
    }

    /*
    |--------------------------------------------------------------------------
    | Status Badge CSS Class
    |--------------------------------------------------------------------------
    */

    public static function badgeClass(?string $status): string
    {
        return match ($status) {
            self::DRAFT        => 'field-status-draft',
            self::SUBMITTED    => 'field-status-submitted',
            self::RESUBMITTED  => 'field-status-submitted',
            self::UNDER_REVIEW => 'field-status-under-review',
            self::RETURNED     => 'field-status-returned',
            self::REJECTED     => 'field-status-rejected',
            self::APPROVED     => 'field-status-approved',

            default => 'field-status-default',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Allowed Workflow Transitions
    |--------------------------------------------------------------------------
    */

   public static function transitions(): array
    {
        return [
            self::DRAFT => [
                self::SUBMITTED,
            ],

            self::SUBMITTED => [
                self::UNDER_REVIEW,
                self::APPROVED,
                self::RETURNED,
                self::REJECTED,
            ],

            self::UNDER_REVIEW => [
                self::APPROVED,
                self::RETURNED,
                self::REJECTED,
            ],

            self::RETURNED => [
                self::RESUBMITTED,
                self::UNDER_REVIEW,
                self::APPROVED,
                self::REJECTED,
            ],

            self::REJECTED => [
                self::RESUBMITTED,
                self::UNDER_REVIEW,
                self::RETURNED,
                self::APPROVED,
            ],

            self::RESUBMITTED => [
                self::UNDER_REVIEW,
                self::APPROVED,
                self::RETURNED,
                self::REJECTED,
            ],

            self::APPROVED => [
                  self::RETURNED,
                  self::UNDER_REVIEW,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Transition
    |--------------------------------------------------------------------------
    */

    public static function canTransition(
        ?string $from,
        ?string $to
    ): bool {
        if ($from === null || $to === null) {
            return false;
        }

        $from = strtolower(trim($from));
        $to = strtolower(trim($to));

        $transitions = self::transitions();

        $allowedTransitions = $transitions[$from] ?? [];

        return in_array($to, $allowedTransitions, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Decision Options
    |--------------------------------------------------------------------------
    */

    public static function reviewDecisionOptions(): array
    {
        return [
            self::APPROVED => 'Approve',
            self::RETURNED => 'Return for Correction',
            self::REJECTED => 'Reject',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status Values For Validation
    |--------------------------------------------------------------------------
    */

    public static function values(): array
    {
        return array_keys(self::labels());
    }
}

