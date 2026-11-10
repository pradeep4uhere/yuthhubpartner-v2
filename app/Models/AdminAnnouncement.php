<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminAnnouncement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'priority',
        'audience_type',
        'target_type',
        'status',
        'attachment_path',
        'attachment_name',
        'published_at',
        'expires_at',
    ];

    /**
     * Cast announcement date fields.
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the Admin who created the announcement.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get all assigned recipients of the announcement.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(
            AdminAnnouncementRecipient::class,
            'announcement_id'
        );
    }
}
