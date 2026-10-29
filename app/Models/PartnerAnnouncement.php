<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerAnnouncement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'partner_id',
        'title',
        'message',
        'priority',
        'target_type',
        'status',
        'attachment_path',
        'attachment_name',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Return the partner who created this announcement.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Return the agents targeted by this announcement.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(
            PartnerAnnouncementRecipient::class,
            'announcement_id'
        );
    }

   /**
     * Return read records through the announcement recipients.
     */
    public function reads(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            PartnerAnnouncementRead::class,
            PartnerAnnouncementRecipient::class,
            'announcement_id',
            'recipient_id',
            'id',
            'id'
        );
    }



}
