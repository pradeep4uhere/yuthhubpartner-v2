<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearnerCourseImportBatch extends Model
{
    protected $fillable = [
        'batch_id',
        'original_filename',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'matched_rows',
        'pending_rows',
        'skipped_rows',
        'failed_rows',
        'invalid_file_path',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'matched_rows' => 'integer',
        'pending_rows' => 'integer',
        'skipped_rows' => 'integer',
        'failed_rows' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get all CSV records belonging to this import batch.
     */
    public function records(): HasMany
    {
        return $this->hasMany(
            LearnerCourseImport::class,
            'import_batch_id',
            'batch_id'
        );
    }
}
