<?php

namespace App\Support;

class EventTransactionReviewStatus
{
    /*
    |--------------------------------------------------------------------------
    | Review Status Constants
    |--------------------------------------------------------------------------
    */

    public const OPEN = 'Open';
    public const PENDING = 'Pending';
    public const ACCEPTED = 'Accepted';
    public const REJECTED = 'Rejected';
    public const RETURN = 'Return';

    /*
    |--------------------------------------------------------------------------
    | Status Labels
    |--------------------------------------------------------------------------
    */

    public static function labels(): array
    {
        return [
            self::OPEN     => 'Open',
            self::PENDING  => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::RETURN   => 'Return',
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

        return self::labels()[$status] ?? $status;
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
