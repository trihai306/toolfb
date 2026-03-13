<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExtensionCommand implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $extensionId,
        public string $command,
        public array $data = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("extension.{$this->extensionId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'extension.command';
    }

    public function broadcastWith(): array
    {
        return [
            'command' => $this->command,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }
}
