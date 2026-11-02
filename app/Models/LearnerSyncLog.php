<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;


class LearnerSyncLog extends Model
{
    use HasFactory;

    protected $table = 'learner_sync_logs';

    protected $fillable = [
        'run_id',
        'sync_type',
        'started_at',
        'completed_at',
        'total_fetched',
        'total_inserted',
        'total_updated',
        'total_failed',
        'total_processed',
        'total_success',
        'total_unchanged',
        'total_duplicates',
        'total_pages',
        'duration_seconds',
        'status',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_fetched' => 'integer',
        'total_inserted' => 'integer',
        'total_updated' => 'integer',
        'total_failed' => 'integer',
        'total_processed' => 'integer',
        'total_success' => 'integer',
        'total_unchanged' => 'integer',
        'total_duplicates' => 'integer',
        'total_pages' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /** Get all page logs belonging to this synchronization run. */
    public function pages(): HasMany
    {
        return $this->hasMany(
            LearnerSyncPageLog::class,
            'learner_sync_log_id'
        );
    }

    /** Get all duplicate and failed record logs belonging to this run. */
    public function recordLogs(): HasMany
    {
        return $this->hasMany(
            LearnerSyncRecordLog::class,
            'learner_sync_log_id'
        );
    }
}
