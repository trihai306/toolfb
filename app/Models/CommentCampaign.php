<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommentCampaign extends Model
{
    protected $fillable = [
        'name', 'status', 'content', 'images', 'groups',
        'settings', 'stats', 'extension_id', 'started_at', 'completed_at',
        'browser_profile_id', 'group_ids',
    ];

    protected $casts = [
        'images' => 'array',
        'groups' => 'array',
        'group_ids' => 'array',
        'settings' => 'array',
        'stats' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function browserProfile(): BelongsTo
    {
        return $this->belongsTo(BrowserProfile::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CommentLog::class, 'campaign_id');
    }

    public function getSuccessCountAttribute(): int
    {
        return $this->logs()->where('status', 'success')->count();
    }

    public function getFailCountAttribute(): int
    {
        return $this->logs()->where('status', 'failed')->count();
    }
}
