<?php

namespace App\Models;

use Database\Factories\PartnerPlacementUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'partner_placement_name', 'contact_number', 'pp_code', 'status', 'password'])]
#[Hidden(['password', 'remember_token'])]
class PartnerPlacementUser extends Authenticatable
{
    /** @use HasFactory<PartnerPlacementUserFactory> */
    use HasFactory;

    use Notifiable;

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
            'placement_partner_user_id'
        );
    }


    public function exports(): MorphMany
    {
        return $this->morphMany(Export::class, 'requested_by');
    }

    public function fieldAgents(): HasMany
    {
        return $this->hasMany(
            FieldAgent::class,
            'placement_partner_user_id',
            'id'
        );
    }


    /**
     * Get all support tickets created by this Placement Partner.
     */
    public function supportTickets(): MorphMany
    {
        return $this->morphMany(
            SupportTicket::class,
            'requester'
        );
    }




}
