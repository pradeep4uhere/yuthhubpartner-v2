<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PartnerCenter;

class EventTransaction extends Model
{
    use HasFactory;

    protected $table = 'event_transactions';

    protected $fillable = [
        'learner_id',
        'partner_id',
        'partner_center_id',
        'partner_placement_user_id',
        'field_agent_id',

        'review_status',
        'workflow_status',

        'beneficiary_phone_number',
        'beneficiary_name',

        'event_master_id',
        'event_master_type_id',

        'event_type_name',
        'event_category_name',

        'event_create_date',
        'event_date_submitted',

        'event_value',

        'field_type_id',
        'industry_type_id',

        'uploaded_doc_1',
        'uploaded_doc_2',
        'uploaded_doc_3',

        'last_comment',
        'uploaded_doc_links',

        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'event_create_date' => 'date',
        'event_date_submitted' => 'date',
        'event_value' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Learner
    |--------------------------------------------------------------------------
    */

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class, 'learner_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Partner
    |--------------------------------------------------------------------------
    */

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Center
    |--------------------------------------------------------------------------
    */

    public function partnerCenter(): BelongsTo
    {
        return $this->belongsTo(
            PartnerCenter::class,
            'partner_center_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Partner Placement User
    |--------------------------------------------------------------------------
    */

    public function partnerPlacementUser(): BelongsTo
    {
        return $this->belongsTo(
            PartnerPlacementUser::class,
            'partner_placement_user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Field Agent
    |--------------------------------------------------------------------------
    */

    public function fieldAgent(): BelongsTo
    {
        return $this->belongsTo(
            FieldAgent::class,
            'field_agent_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Event Transaction Logs
    |--------------------------------------------------------------------------
    */

    public function logs(): HasMany
    {
        return $this->hasMany(
            EventTransactionLog::class,
            'event_transaction_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Log
    |--------------------------------------------------------------------------
    */

    public function latestLog()
    {
        return $this->hasOne(
            EventTransactionLog::class,
            'event_transaction_id'
        )->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('workflow_status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('workflow_status', 'submitted');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('workflow_status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('workflow_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('workflow_status', 'rejected');
    }
}
