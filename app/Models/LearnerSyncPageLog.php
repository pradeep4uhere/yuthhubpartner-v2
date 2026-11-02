<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerSyncPageLog extends Model
{
    use HasFactory;

    protected $table = 'learner_sync_page_logs';

    protected $fillable = [
        'learner_sync_log_id',
        'page_number',
        'records_fetched',
        'total_records',
        'records_remaining',
        'status',
        'error_message',
        'fetched_at',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'records_fetched' => 'integer',
        'total_records' => 'integer',
        'records_remaining' => 'integer',
        'fetched_at' => 'datetime',
    ];

    /** Get the parent synchronization run for this page. */
    public function syncLog(): BelongsTo
    {
        return $this->belongsTo(
            LearnerSyncLog::class,
            'learner_sync_log_id'
        );
    }
}
