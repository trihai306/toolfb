<?php

namespace App\Jobs;

use App\Events\ExtensionCommand;
use App\Models\CommentCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCommentCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public CommentCampaign $campaign,
    ) {}

    public function handle(): void
    {
        $campaign = $this->campaign;

        if ($campaign->status !== 'pending') {
            Log::info("[AutoFB] Campaign #{$campaign->id} is not pending, skipping");
            return;
        }

        $campaign->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $extensionId = $campaign->extension_id;
        if (!$extensionId) {
            Log::warning("[AutoFB] Campaign #{$campaign->id} has no extension_id");
            $campaign->update(['status' => 'failed', 'completed_at' => now()]);
            return;
        }

        // Broadcast START_COMMENTING to the extension
        broadcast(new ExtensionCommand(
            extensionId: $extensionId,
            command: 'START_COMMENTING',
            data: [
                'campaign_id' => $campaign->id,
                'content' => $campaign->content,
                'images' => $campaign->images ?? [],
                'groups' => $campaign->groups ?? [],
                'settings' => $campaign->settings ?? [
                    'commentsPerGroup' => 3,
                    'scrollDepth' => 5,
                    'commentMinDelay' => 15,
                    'commentMaxDelay' => 45,
                ],
            ],
        ));

        Log::info("[AutoFB] Dispatched START_COMMENTING for Campaign #{$campaign->id} to extension {$extensionId}");
    }
}
