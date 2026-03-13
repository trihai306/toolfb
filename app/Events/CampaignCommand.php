<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CampaignCommand implements ShouldBroadcastNow
{
    use Dispatchable;

    public string $command;
    public array $payload;
    public string $extensionId;

    public function __construct(string $extensionId, string $command, array $payload = [])
    {
        $this->extensionId = $extensionId;
        $this->command = $command;
        $this->payload = $payload;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("autofb.{$this->extensionId}");
    }

    public function broadcastAs(): string
    {
        return $this->command;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
