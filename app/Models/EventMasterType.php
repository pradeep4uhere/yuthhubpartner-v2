<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventMasterType extends Model
{
    use HasFactory;

    protected $table = 'event_master_type';

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function categories()
    {
        return $this->hasMany(
            EventCategoryMaster::class,
            'event_master_type_id'
        );
    }

    public function eventMasters()
    {
        return $this->hasMany(
            EventMaster::class,
            'event_master_type_id'
        );
    }
}
