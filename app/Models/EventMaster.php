<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EventMasterType;
use App\Models\EventCategoryMaster;

class EventMaster extends Model
{
    use HasFactory;

    protected $table = 'event_master';

    protected $fillable = [
        'event_master_type_id',
        'event_category_id',
        'event_name',
        'description',
        'eligibility',
        'fees_per_transactions',
        'document_name_1',
        'document_name_2',
        'document_name_3',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'fees_per_transactions' => 'decimal:2',
    ];

    public function eventMasterType()
    {
        return $this->belongsTo(
            EventMasterType::class,
            'event_master_type_id'
        );
    }

    public function eventCategory()
    {
        return $this->belongsTo(
            EventCategoryMaster::class,
            'event_category_id'
        );
    }

}
