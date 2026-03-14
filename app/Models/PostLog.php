<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostLog extends Model
{
    protected $fillable = [
        'scheduled_post_id',
        'browser_profile_id',
        'group_id',
        'group_name',
        'status',
        'content_preview',
        'error',
        'post_url',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function scheduledPost(): BelongsTo
    {
        return $this->belongsTo(ScheduledPost::class);
    }

    public function browserProfile(): BelongsTo
    {
        return $this->belongsTo(BrowserProfile::class);
    }
}
