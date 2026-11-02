<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerSyncRecordLog extends Model
{
    use HasFactory;

    protected $table = 'learner_sync_record_logs';

    protected $fillable = [
        'learner_sync_log_id',
        'page_number',
        'record_type',
        'phone_number',
        'normalized_mobile',
        'matching_learner_ids',
        'source_payload',
        'reason',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'matching_learner_ids' => 'array',
        'source_payload' => 'array',
    ];

    /** Get the parent synchronization run for this record issue. */
    public function syncLog(): BelongsTo
    {
        return $this->belongsTo(
            LearnerSyncLog::class,
            'learner_sync_log_id'
        );
    }
}
