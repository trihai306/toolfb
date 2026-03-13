<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentTemplate extends Model
{
    protected $fillable = [
        'name', 'content', 'images', 'tags', 'usage_count',
    ];

    protected $casts = [
        'images' => 'array',
        'tags' => 'array',
    ];
}
