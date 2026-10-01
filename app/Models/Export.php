<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Export extends Model
{
    protected $table = 'exports';

    protected $fillable = [
        'requested_by_type',
        'requested_by_id',
        'type',
        'scope_type',
        'scope_id',
        'title',
        'filters',
        'file_name',
        'file_path',
        'status',
        'total_records',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_records' => 'integer',
    ];

    /**
     * Person/entity who requested the export.
     *
     * Admin
     * Partner
     * PlacementPartnerUser
     * FieldAgent
     */
    public function requestedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
