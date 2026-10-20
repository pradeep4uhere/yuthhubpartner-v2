<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    /**
     * The attributes that can be mass assigned.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_number',
        'category_id',
        'subject',
        'description',
        'affected_module',
        'priority',
        'status',
        'last_reply_at',
        'resolved_at',
        'closed_at',
    ];

    /**
     * Get the user who raised the ticket.
     *
     * Supports FieldAgent, Partner, PartnerCenter and
     * PartnerPlacementUser through the polymorphic relationship.
     *
     * @return MorphTo
     */
    public function requester(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the category associated with this ticket.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            SupportTicketCategory::class,
            'category_id'
        );
    }

    /**
     * Get the user or account to which this ticket is assigned.
     *
     * @return MorphTo
     */
    public function assignedTo(): MorphTo
    {
        return $this->morphTo('assigned_to');
    }

    /**
     * Get all replies associated with this ticket, ordered oldest first.
     *
     * @return HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(
            SupportTicketReply::class,
            'support_ticket_id'
        )->orderBy('created_at');
    }

    /**
     * Determine whether the ticket is currently open for replies.
     *
     * @return bool
     */
    public function isOpenForReplies(): bool
    {
        return ! in_array(
            $this->status,
            ['closed'],
            true
        );
    }


    /**
     * Get all attachments uploaded for this support ticket.
     *
     * Attachments may belong directly to the ticket or to one of its replies.
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(
            SupportTicketAttachment::class,
            'support_ticket_id'
        );
    }





}
