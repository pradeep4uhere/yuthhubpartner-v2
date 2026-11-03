<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerCourseImport extends Model
{
    protected $table = 'learner_course_imports';

    protected $fillable = [
        'import_batch_id',
        'course_name',
        'contact',
        'first_name',
        'last_name',
        'my_groups',
        'age_in_years',
        'gender',
        'state',
        'data_sharing_consent',
        'course_completion',
        'learner_id',
        'match_status',
        'matched_at',
    ];

    protected $casts = [
        'course_completion' => 'integer',
        'matched_at' => 'datetime',
    ];

    /**
     * Get the learner matched with this imported CSV record.
     */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class, 'learner_id');
    }


    /**
     * Get the import batch associated with this CSV record.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            LearnerCourseImportBatch::class,
            'import_batch_id',
            'batch_id'
        );
    }
}
