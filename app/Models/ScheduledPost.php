<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends Model
{
    protected $fillable = [
        'browser_profile_id',
        'content',
        'images',
        'group_ids',
        'settings',
        'status',
        'results',
        'scheduled_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'images' => 'array',
        'group_ids' => 'array',
        'settings' => 'array',
        'results' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // === Relationships ===

    public function browserProfile(): BelongsTo
    {
        return $this->belongsTo(BrowserProfile::class);
    }

    // === Scopes ===

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeForProfile(Builder $query, int $profileId): Builder
    {
        return $query->where('browser_profile_id', $profileId);
    }

    // === Helpers ===

    public function markProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(array $results = []): void
    {
        $this->update([
            'status' => 'completed',
            'results' => $results,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(array $results = []): void
    {
        $this->update([
            'status' => 'failed',
            'results' => $results,
            'completed_at' => now(),
        ]);
    }
}
