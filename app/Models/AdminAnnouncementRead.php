<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAnnouncementRead extends Model
{
    protected $fillable = [
        'recipient_id',
        'read_at',
    ];

    /**
     * Cast the read timestamp.
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Get the recipient record associated with this read entry.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(
            AdminAnnouncementRecipient::class,
            'recipient_id'
        );
    }
}
