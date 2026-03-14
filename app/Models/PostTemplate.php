<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostTemplate extends Model
{
    protected $fillable = [
        'name',
        'category',
        'content',
        'images',
        'tags',
        'seed_comments',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'tags' => 'array',
        'seed_comments' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
