<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustryType extends Model
{
    protected $table = 'industry_types';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function eventTransactions(): HasMany
    {
        return $this->hasMany(EventTransaction::class, 'industry_type_id', 'id');
    }
}
