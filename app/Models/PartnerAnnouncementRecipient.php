<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PartnerAnnouncementRecipient extends Model
{
    protected $fillable = [
        'announcement_id',
        'field_agent_id',
    ];

    /**
     * Return the announcement associated with this recipient.
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(
            PartnerAnnouncement::class,
            'announcement_id'
        );
    }

    /**
     * Return the targeted field agent.
     */
    public function fieldAgent(): BelongsTo
    {
        return $this->belongsTo(FieldAgent::class);
    }

    /**
     * Return the recipient's read record.
     */
    public function read(): HasOne
    {
        return $this->hasOne(
            PartnerAnnouncementRead::class,
            'recipient_id'
        );
    }
}
