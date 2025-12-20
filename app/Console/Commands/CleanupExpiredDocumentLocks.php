<?php

namespace App\Console\Commands;

use App\Events\DocumentUnlocked;
use App\Models\DocumentLock;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 期限切れドキュメントロックのクリーンアップコマンド
 * 
 * 有効期限（expires_at）が過去のロックを削除し、WebSocketで通知します。
 * 1分ごとに実行されることを想定しています。
 */
class CleanupExpiredDocumentLocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-expired-document-locks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期限切れのドキュメントロックを削除';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 期限切れのロックを取得
        $expiredLocks = DocumentLock::where('expires_at', '<=', now())->get();

        if ($expiredLocks->isEmpty()) {
            $this->info('期限切れのロックはありません');
            return Command::SUCCESS;
        }

        $this->info("期限切れのロック: {$expiredLocks->count()}件を削除します");

        $deletedCount = 0;
        foreach ($expiredLocks as $lock) {
            $roomId = $lock->room_id;
            $sessionId = $lock->session_id;

            // ロックを削除
            $lock->delete();
            $deletedCount++;

            // WebSocketで通知
            if (config('broadcasting.default') !== 'null') {
                try {
                    $event = new DocumentUnlocked($roomId, $sessionId);
                    $pendingBroadcast = broadcast($event);
                    unset($pendingBroadcast);
                } catch (BroadcastException $e) {
                    Log::error('Failed to broadcast document unlock: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error('Failed to broadcast document unlock: ' . $e->getMessage());
                }
            }
        }

        $this->info("{$deletedCount}件のロックを削除しました");
        return Command::SUCCESS;
    }
}
