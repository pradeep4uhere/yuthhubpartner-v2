<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportTicketReply extends Model
{
    use HasFactory;

    /**
     * The attributes that can be mass assigned.
     *
     * Sender identity and ticket association should be set through
     * Eloquent relationships, rather than trusted from request input.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message',
        'is_internal_note',
    ];

    /**
     * Cast reply attributes to their expected data types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_internal_note' => 'boolean',
        ];
    }

    /**
     * Get the ticket associated with this reply.
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
     * Get the user or staff account that sent this reply.
     *
     * Supports different authenticated account models through
     * Laravel's polymorphic relationship.
     *
     * @return MorphTo
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all files attached to this support ticket reply.
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(
            SupportTicketAttachment::class,
            'support_ticket_reply_id'
        );
    }
}

