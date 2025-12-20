<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ドキュメントロック取得イベント
 * 
 * ロックが取得された際に、同じルームの他のユーザーに通知します。
 */
class DocumentLocked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomId;
    public string $sessionId;
    public string $lockedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(string $roomId, string $sessionId)
    {
        $this->roomId = $roomId;
        $this->sessionId = $sessionId;
        $this->lockedAt = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('document.' . $this->roomId),
        ];
    }

    /**
     * ブロードキャストされるイベント名を取得
     */
    public function broadcastAs(): string
    {
        return 'document.locked';
    }
}
