<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentLog extends Model
{
    protected $fillable = [
        'campaign_id', 'group_id', 'group_name', 'post_url',
        'comment_content', 'status', 'error_message', 'commented_at',
    ];

    protected $casts = [
        'commented_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CommentCampaign::class, 'campaign_id');
    }
}
