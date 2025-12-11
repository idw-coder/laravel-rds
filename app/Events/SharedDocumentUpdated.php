<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 共有ドキュメント更新イベントクラス
 *
 * このイベントは共有ドキュメントが更新された際にブロードキャストされます。
 * ShouldBroadcastインターフェースを実装することで、WebSocketを通じて
 * リアルタイムにクライアントへ通知を送信します。
 *
 * 使用例:
 *   event(new SharedDocumentUpdated($roomId, $content));
 *   // または
 *   SharedDocumentUpdated::dispatch($roomId, $content);
 */
class SharedDocumentUpdated implements ShouldBroadcast
{
    // Dispatchable: イベントのディスパッチを簡単にするヘルパーメソッドを提供
    // InteractsWithSockets: ソケット接続との相互作用を管理
    // SerializesModels: Eloquentモデルを適切にシリアライズ
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * ドキュメントが属するルーム（部屋）のID
     * このIDを使ってチャンネル名が動的に生成されます
     */
    public string $roomId;

    /**
     * 更新されたドキュメントの内容
     * クライアント側でこの内容を受け取り、表示を更新します
     */
    public string $content;

    /**
     * 新しいイベントインスタンスを作成
     *
     * @param string $roomId  ドキュメントのルームID
     * @param string $content 更新されたドキュメントの内容
     */
    public function __construct(string $roomId, string $content)
    {
        $this->roomId = $roomId;
        $this->content = $content;
    }

    /**
     * イベントがブロードキャストされるチャンネルを取得
     *
     * Channel（パブリックチャンネル）を使用しているため、
     * 認証なしで誰でも購読可能です。
     * 認証が必要な場合は PrivateChannel を使用してください。
     *
     * @return array<int, Channel> ブロードキャスト先のチャンネル配列
     */
    public function broadcastOn(): array
    {
        // 'document.{roomId}' という形式のチャンネル名でブロードキャスト
        // 例: roomId が 'abc123' の場合、'document.abc123' チャンネルに送信
        return [
            new Channel('document.' . $this->roomId),
        ];
    }

    /**
     * ブロードキャストされるイベント名を取得
     *
     * クライアント側でこのイベント名をリッスンします。
     * 例（JavaScript）:
     *   Echo.channel('document.abc123')
     *       .listen('.document.updated', (e) => {
     *           console.log(e.content);
     *       });
     *
     * @return string イベント名
     */
    public function broadcastAs(): string
    {
        return 'document.updated';
    }
}