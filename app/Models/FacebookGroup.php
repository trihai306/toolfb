<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookGroup extends Model
{
    protected $fillable = [
        'browser_profile_id',
        'group_id',
        'name',
        'category',
        'url',
        'image',
        'member_count',
        'privacy',
    ];

    public function browserProfile(): BelongsTo
    {
        return $this->belongsTo(BrowserProfile::class);
    }
}
