<?php

namespace App\Models;

use Database\Factories\FieldAgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        ];
    }

    public function eventTransactions()
    {
        return $this->hasMany(
            EventTransaction::class,
            'field_agent_id'
        );
    }
}
