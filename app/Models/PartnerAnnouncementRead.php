<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerAnnouncementRead extends Model
{
    protected $fillable = [
        'recipient_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Return the recipient associated with this read record.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(
            PartnerAnnouncementRecipient::class,
            'recipient_id'
        );
    }
}
