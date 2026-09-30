<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldAgentSakhiHistory extends Model
{
    protected $fillable = [
        'field_agent_id',
        'old_sakhi_id',
        'new_sakhi_id',
        'changed_by',
        'changed_at',
        'remarks',
    ];


}
