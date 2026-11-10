<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdminAnnouncementRecipient extends Model
{
    protected $fillable = [
        'announcement_id',
        'recipient_type',
        'recipient_id',
    ];

    /**
     * Get the associated Admin announcement.
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(
            AdminAnnouncement::class,
            'announcement_id'
        );
    }

    /**
     * Get the actual recipient from its respective module.
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the read record for this recipient.
     */
    public function read(): HasOne
    {
        return $this->hasOne(
            AdminAnnouncementRead::class,
            'recipient_id'
        );
    }
}
