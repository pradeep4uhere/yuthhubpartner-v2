<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketAttachment extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'support_ticket_reply_id',
        'uploaded_by_type',
        'uploaded_by_id',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    /**
     * Get the ticket associated with this attachment.
     *
     * @return BelongsTo
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(
            SupportTicket::class,
            'support_ticket_id'
        );
    }

    /**
     * Get the reply associated with this attachment, if applicable.
     *
     * @return BelongsTo
     */
    public function reply(): BelongsTo
    {
        return $this->belongsTo(
            SupportTicketReply::class,
            'support_ticket_reply_id'
        );
    }





   /**
 * Get the entity that uploaded this attachment.
 *
 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
 */
public function uploaded_by(): \Illuminate\Database\Eloquent\Relations\MorphTo
{
    return $this->morphTo(
        'uploaded_by',
        'uploaded_by_type',
        'uploaded_by_id'
    );
}





}
