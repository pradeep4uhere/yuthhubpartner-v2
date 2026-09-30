<?php

namespace App\Models;

use Database\Factories\PartnerPlacementUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        ];
    }


    public function eventTransactions()
    {
        return $this->hasMany(
            EventTransaction::class,
            'partner_placement_user_id'
        );
    }

}
