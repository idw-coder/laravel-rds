<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PostService;

/**
 * 下書きリマインダーバッチ処理
 * 
 * 【実装状況】
 * Command作成完了
 * Service層実装完了  
 * Model層実装完了（スコープ追加）
 * スケジュール自動実行は未実装（routes/console.phpは触らない方針）
 * 
 * 【使用方法】
 * 手動実行のみ可能:
 * - 確認のみ: ./vendor/bin/sail artisan app:remind-draft-posts
 * - 削除実行: ./vendor/bin/sail artisan app:remind-draft-posts --delete
 * 
 * 【今後の課題】
 * - スケジュール実行機能の実装方法を確認後、routes/console.phpに設定を追加
 * - 本番環境でのcron設定
 */

class RemindDraftPosts extends Command
{
    /**
     * コマンド名とオプション定義
     * 実行方法: php artisan app:remind-draft-posts
     * オプション付き: php artisan app:remind-draft-posts --delete
     *
     * @var string
     */
    protected $signature = 'app:remind-draft-posts {--delete : 古い下書きを削除する}';

    /**
     * コマンドの説明（artisan listで表示される）
     *
     * @var string
     */
    protected $description = '30日以上更新されていない下書き投稿を検出・削除';

    protected PostService $postService;

    /**
     * コンストラクタ - PostServiceを注入
     */
    public function __construct(PostService $postService)
    {
        parent::__construct();
        $this->postService = $postService;
    }

    /**
     * バッチ処理の実行内容
     * Command（入り口） → Service（ビジネスロジック） → Model（DB操作）
     */
    public function handle()
    {
        // Service経由で古い下書きを取得
        $oldDrafts = $this->postService->getOldDrafts(30);

        // 該当データがない場合
        if ($oldDrafts->isEmpty()) {
            $this->info('放置された下書きはありません');
            return Command::SUCCESS;
        }

        // 検出結果を表示
        $this->info("放置された下書き: {$oldDrafts->count()}件");
        
        foreach ($oldDrafts as $draft) {
            $this->line("- ID:{$draft->id} | ユーザー:{$draft->user->name} | タイトル:{$draft->title}");
        }

        // --deleteオプションが指定された場合は削除処理
        if ($this->option('delete')) {
            if ($this->confirm('これらの下書きを削除しますか？')) {
                $count = $this->postService->deleteOldDrafts(30);
                $this->info("{$count}件の下書きを削除しました");
            } else {
                $this->info('削除をキャンセルしました');
            }
        }

        return Command::SUCCESS;
    }
}