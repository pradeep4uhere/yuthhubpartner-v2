<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCategoryMaster extends Model
{
    use HasFactory;

    protected $table = 'event_category_master';

    protected $fillable = [
        'event_master_type_id',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function eventMasterType()
    {
        return $this->belongsTo(
            EventMasterType::class,
            'event_master_type_id'
        );
    }

    public function eventMasters()
    {
        return $this->hasMany(
            EventMaster::class,
            'event_category_id'
        );
    }
}
