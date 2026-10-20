<?php

namespace App\Models;

use Database\Factories\FieldAgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'name',
    'email',
    'contact_number',
    'csc_id',
    'sakhi_id',
    'partner_id',
    'partner_center_id',
    'placement_partner_user_id',
    'state',
    'district',
    'status',
    'password',
    'mpin_hash',
    'mpin_login_enabled'
])]
#[Hidden(['password', 'remember_token'])]
class FieldAgent extends Authenticatable
{
    /** @use HasFactory<FieldAgentFactory> */
    use HasFactory;

    use Notifiable;

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function partnerCenter(): BelongsTo
    {
        return $this->belongsTo(PartnerCenter::class);
    }

    public function placementPartnerUser(): BelongsTo
    {
        return $this->belongsTo(PartnerPlacementUser::class);
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'mpin_hash' => 'hashed',
            'mpin_login_enabled' => 'boolean',
        ];
    }

    public function eventTransactions()
    {
        return $this->hasMany(
            EventTransaction::class,
            'field_agent_id'
        );
    }


    public function exports(): MorphMany
    {
        return $this->morphMany(Export::class, 'requested_by');
    }



    /**
     * Get all support tickets raised by this Field Agent.
     *
     * @return MorphMany
     */
    public function supportTickets(): MorphMany
    {
        return $this->morphMany(
            SupportTicket::class,
            'requester'
        );
    }





}
