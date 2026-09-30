<?php

namespace App\Models;

use Database\Factories\PartnerCenterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['partner_id', 'partner_code', 'name', 'contact_number', 'email', 'status', 'password'])]
#[Hidden(['password', 'remember_token'])]
class PartnerCenter extends Authenticatable
{
    /** @use HasFactory<PartnerCenterFactory> */
    use HasFactory;

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
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
            'partner_center_id'
        );
    }
}
