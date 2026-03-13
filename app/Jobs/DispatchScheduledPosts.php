<?php

namespace App\Jobs;

use App\Events\ExtensionCommand;
use App\Models\ScheduledPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchScheduledPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $duePosts = ScheduledPost::due()
            ->with('browserProfile')
            ->get();

        if ($duePosts->isEmpty()) {
            return;
        }

        Log::info("[AutoFB] Found {$duePosts->count()} scheduled posts due for dispatch");

        foreach ($duePosts as $post) {
            $profile = $post->browserProfile;

            if (!$profile || !$profile->extension_id) {
                Log::warning("[AutoFB] ScheduledPost #{$post->id} — profile missing or no extension_id, marking failed");
                $post->markFailed(['error' => 'Browser profile not found or missing extension_id']);
                continue;
            }

            // Mark as processing
            $post->markProcessing();

            // Resolve group names from IDs
            $groups = $profile->groups()
                ->whereIn('group_id', $post->group_ids)
                ->get()
                ->map(fn($g) => [
                    'id' => $g->id,
                    'groupId' => $g->group_id,
                    'name' => $g->name,
                    'url' => $g->url,
                ])
                ->values()
                ->toArray();

            if (empty($groups)) {
                $post->markFailed(['error' => 'No matching groups found']);
                continue;
            }

            // Broadcast START_POSTING command to extension
            broadcast(new ExtensionCommand(
                extensionId: $profile->extension_id,
                command: 'START_POSTING',
                data: [
                    'scheduled_post_id' => $post->id,
                    'content' => $post->content,
                    'images' => $post->images ?? [],
                    'groups' => $groups,
                    'settings' => $post->settings ?? [
                        'minDelay' => 30,
                        'maxDelay' => 90,
                    ],
                ],
            ));

            Log::info("[AutoFB] Dispatched START_POSTING for ScheduledPost #{$post->id} to extension {$profile->extension_id}");
        }
    }
}
