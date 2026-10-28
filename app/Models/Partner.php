<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\SupportTicket;

#[Fillable(['partner_code', 'name', 'contact_number', 'email', 'status', 'password'])]
#[Hidden(['password', 'remember_token'])]
class Partner extends Authenticatable
{
    use HasFactory;



    public function supportTickets(): MorphMany
    {
        return $this->morphMany(
            SupportTicket::class,
            'requester'
        );
    }

    public function centers(): HasMany
    {
        return $this->hasMany(PartnerCenter::class);
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
            'partner_id'
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
            'partner_id'
        );
    }



}
