<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupportTicketCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that can be mass assigned.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    /**
     * Cast category attributes to their expected data types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get all tickets belonging to this category.
     *
     * @return HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(
            SupportTicket::class,
            'category_id'
        );
    }
}
