<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['partner_code', 'name', 'contact_number', 'email', 'status', 'password'])]
#[Hidden(['password', 'remember_token'])]
class Partner extends Authenticatable
{
    use HasFactory;

    public function centers(): HasMany
    {
        return $this->hasMany(PartnerCenter::class);
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
            'partner_id'
        );
    }
}
