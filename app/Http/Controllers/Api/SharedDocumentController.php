<?php

namespace App\Http\Controllers\Api;

use App\Events\DocumentLocked;
use App\Events\DocumentUnlocked;
use App\Events\SharedDocumentUpdated;
use App\Http\Controllers\Controller;
use App\Models\DocumentLock;
use App\Models\SharedDocument;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SharedDocumentController extends Controller
{
    // ============================================
    // ドキュメント操作
    // ============================================

    /**
     * ドキュメント取得（なければ新規作成）
     */
    public function show(string $roomId)
    {
        $document = SharedDocument::firstOrCreate(
            ['room_id' => $roomId], // 第一引数のroom_idで検索、見つかればそれを使用
            ['content' => '']       // 見つからなければ第二引数の内容を使用
        );

        return response()->json([
            'room_id' => $document->room_id,
            'content' => $document->content,
        ]);
    }

    /**
     * ドキュメント保存
     * 
     * 注意: ロック確認はフロントエンドで行う（バックエンドではロック確認なし）
     */
    public function update(Request $request, string $roomId)
    {
        $request->validate([
            'content' => 'nullable|string',
        ]);

        $document = SharedDocument::firstOrCreate(
            ['room_id' => $roomId],
            ['content' => '']
        );

        $oldContent = $document->content ?? '';
        $newContent = $request->input('content', '');

        // 削除された画像ファイルをクリーンアップ
        if ($newContent !== $oldContent && $document->id) {
            $this->cleanupDeletedImages($document->id, $oldContent, $newContent);
        }

        $document->content = $newContent;
        $document->save();

        $this->broadcast(new SharedDocumentUpdated($roomId, $document->content));

        return response()->json([
            'room_id' => $document->room_id,
            'content' => $document->content,
        ]);
    }

    // ============================================
    // 画像操作
    // ============================================

    /**
     * 画像アップロード
     * 
     * 保存先: storage/app/public/shared-documents/{documentId}/
     * アクセス: asset('storage/' . $path) でURL取得
     */
    public function uploadImage(Request $request, string $roomId)
    {
        $document = SharedDocument::firstOrCreate(
            ['room_id' => $roomId],
            ['content' => '']
        );

        $request->validate([
            'image' => 'required|file|image|max:5120', // 5MB以下
        ]);

        $file = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("shared-documents/{$document->id}", $filename, 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
            'path' => $path,
        ], 201);
    }

    /**
     * 画像削除
     */
    public function deleteImage(Request $request, string $roomId, string $filename)
    {
        $document = SharedDocument::where('room_id', $roomId)->firstOrFail();

        if (!$this->deleteImageFile($document->id, $filename)) {
            return response()->json([
                'message' => '画像の削除に失敗しました。ファイルが存在しないか、他のドキュメントで使用されています。',
            ], 404);
        }

        return response()->json(['message' => '画像を削除しました。']);
    }

    // ============================================
    // ロック操作
    // ============================================

    /**
     * ロック取得
     * 
     * トランザクション + 行ロックでレースコンディション対策
     */
    public function lock(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();

        return DB::transaction(function () use ($roomId, $sessionId) {
            // lockForUpdate(): SELECT ... FOR UPDATE を発行し、トランザクション終了まで行をロック
            $existingLock = DocumentLock::where('room_id', $roomId)
                ->lockForUpdate()
                ->first();

            // 他のユーザーがロック中
            if ($existingLock && $existingLock->session_id !== $sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'already_locked',
                    'message' => '他のユーザーが編集中です',
                    'locked_at' => $existingLock->locked_at->toIso8601String(),
                ], 409);
            }

            // ロックを更新（既存があれば削除して再作成）
            DocumentLock::where('room_id', $roomId)->delete();
            $lock = DocumentLock::create([
                'room_id' => $roomId,
                'session_id' => $sessionId,
                'locked_at' => now(),
                'expires_at' => '2038-01-19 03:14:07', // 実質無期限（DELETE /lockで明示的に削除）
            ]);

            $this->broadcast(new DocumentLocked($roomId, $sessionId));

            return response()->json([
                'success' => true,
                'locked_at' => $lock->locked_at->toIso8601String(),
            ]);
        });
    }

    /**
     * ロック解放
     */
    public function unlock(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();

        return DB::transaction(function () use ($roomId, $sessionId) {
            $lock = DocumentLock::where('room_id', $roomId)
            // lockForUpdate(): SELECT ... FOR UPDATE を発行し、トランザクション終了まで行をロック
                ->lockForUpdate()
                ->first();

            if (!$lock) {
                return response()->json([
                    'success' => true,
                    'message' => 'ロックは既に解放されています。',
                ]);
            }

            if ($lock->session_id !== $sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'not_locked_by_user',
                    'message' => 'あなたはロックを保持していません',
                ], 403);
            }

            $lock->delete();
            $this->broadcast(new DocumentUnlocked($roomId, $sessionId));

            return response()->json([
                'success' => true,
                'message' => 'ロックを解放しました。',
            ]);
        });
    }

    /**
     * ロック状態確認
     */
    public function getLockStatus(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();
        $lock = DocumentLock::where('room_id', $roomId)->first();

        return response()->json([
            'is_locked' => (bool) $lock,
            'is_my_lock' => $lock && $lock->session_id === $sessionId,
            'locked_at' => $lock?->locked_at->toIso8601String(),
        ]);
    }

    // ============================================
    // プライベートメソッド
    // ============================================

    /**
     * WebSocketブロードキャスト（共通処理）
     */
    private function broadcast($event): void
    {
        if (config('broadcasting.default') === 'null') {
            return;
        }

        try {
            $pendingBroadcast = broadcast($event);
            unset($pendingBroadcast);
        } catch (BroadcastException $e) {
            Log::error('Broadcast failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Broadcast failed: ' . $e->getMessage());
        }
    }

    /**
     * 削除された画像をクリーンアップ
     */
    private function cleanupDeletedImages(int $documentId, string $oldContent, string $newContent): void
    {
        try {
            $oldUrls = $this->extractImageUrls($oldContent);
            $newUrls = $this->extractImageUrls($newContent);
            $deletedUrls = array_diff($oldUrls, $newUrls);

            foreach ($deletedUrls as $url) {
                if (preg_match('/\/storage\/shared-documents\/' . preg_quote($documentId, '/') . '\/([^\/]+)$/', $url, $match)) {
                    $this->deleteImageFile($documentId, $match[1]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to cleanup images: ' . $e->getMessage());
        }
    }

    /**
     * コンテンツから画像URLを抽出
     */
    private function extractImageUrls(string $content): array
    {
        preg_match_all('/!\[.*?\]\((.*?)\)/', $content, $matches);
        return $matches[1];
    }

    /**
     * 画像ファイルを削除
     */
    private function deleteImageFile(int $documentId, string $filename): bool
    {
        $filePath = "shared-documents/{$documentId}/{$filename}";

        if (!Storage::disk('public')->exists($filePath)) {
            return false;
        }

        // 他のドキュメントで使用中かチェック
        // ※ユーザーが意図的に画像URLをコピペして別ドキュメントに貼り付けた場合に発生し得る
        // ※通常のフローでは各ドキュメントは専用フォルダに画像を保存するため起こりにくい
        // $isUsedElsewhere = SharedDocument::where('id', '!=', $documentId)
        //     ->where('content', 'like', '%' . $filename . '%')
        //     ->exists();

        // if ($isUsedElsewhere) {
        //     return false;
        // }

        return Storage::disk('public')->delete($filePath);
    }
}
