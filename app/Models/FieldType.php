<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldType extends Model
{
    protected $table = 'field_types';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function eventTransactions(): HasMany
    {
        return $this->hasMany(EventTransaction::class, 'field_type_id', 'id');
    }
}
