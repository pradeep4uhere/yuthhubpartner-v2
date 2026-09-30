<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;


class EventTransactionLog extends Model
{
    use HasFactory;

    protected $table = 'event_transaction_logs';

    protected $fillable = [
        'event_transaction_id',

        'action',

        'old_status',
        'new_status',

        'actor_type',

        'user_id',
        'user_type',

        'comment',

        'old_values',
        'new_values',

        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];


    public function user()
    {
        return match ($this->user_type) {
            'Admin' => $this->belongsTo(
                Admin::class,
                'user_id'
            ),

            'FieldAgent' => $this->belongsTo(
                FieldAgent::class,
                'user_id'
            ),

            'Partner' => $this->belongsTo(
                Partner::class,
                'user_id'
            ),

            'PartnerPlacementUser' => $this->belongsTo(
                PartnerPlacementUser::class,
                'user_id'
            ),

            'PartnerCenter' => $this->belongsTo(
                PartnerCenter::class,
                'user_id'
            ),

            default => $this->belongsTo(
                Admin::class,
                'user_id'
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Event Transaction
    |--------------------------------------------------------------------------
    */

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            EventTransaction::class,
            'event_transaction_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actor
    |--------------------------------------------------------------------------
    |
    | actor_type tells us:
    |
    | frontend
    | backend
    | system
    |
    */

    public function getActorNameAttribute()
    {
        if (!$this->user_id || !$this->user_type) {
            return 'System';
        }

        $model = match ($this->user_type) {
            'Admin' => Admin::class,
            'FieldAgent' => FieldAgent::class,
            'Partner' => Partner::class,
            'PartnerPlacementUser' => PartnerPlacementUser::class,
            default => null,
        };

        if (!$model) {
            return 'Unknown User';
        }

        return $model::find($this->user_id);
    }
}
