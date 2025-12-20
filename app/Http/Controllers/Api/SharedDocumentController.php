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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SharedDocumentController extends Controller
{
    /**
     * ドキュメント取得（なければ新規作成）
     */
    public function show(string $roomId)
    {
        $document = SharedDocument::firstOrCreate(
            ['room_id' => $roomId],
            ['content' => '']
        );

        return response()->json([
            'room_id' => $document->room_id,
            'content' => $document->content,
        ]);
    }

    /**
     * ドキュメント保存
     * 保存後、WebSocketで他のユーザーに通知
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

        // contentが更新される場合、削除された画像ファイルを削除
        if ($newContent !== $oldContent && $document->id) {
            try {
                $this->deleteUnusedImages($document->id, $oldContent, $newContent);
            } catch (\Exception $e) {
                // 画像削除でエラーが発生してもドキュメント保存は続行
                Log::error('Failed to delete unused images: ' . $e->getMessage());
            }
        }

        $document->content = $newContent;
        $document->save();

        // WebSocketでブロードキャスト
        // 同じルームの他のユーザーにリアルタイムで通知
        // ブロードキャスト設定が有効な場合のみ実行
        if (config('broadcasting.default') !== 'null') {
            try {
                $event = new SharedDocumentUpdated($roomId, $document->content);
                $pendingBroadcast = broadcast($event);
                // 変数を保持することで、try-catchのスコープ内で__destruct()が呼ばれる
                unset($pendingBroadcast);
            } catch (BroadcastException $e) {
                // BroadcastExceptionを明示的にキャッチ
                Log::error('Failed to broadcast document update: ' . $e->getMessage());
            } catch (\Exception $e) {
                // その他の例外もキャッチ
                Log::error('Failed to broadcast document update: ' . $e->getMessage());
            }
        }

        return response()->json([
            'room_id' => $document->room_id,
            'content' => $document->content,
        ]);
    }

    /**
     * 共有ドキュメント用画像をアップロード
     * 
     * 画像は storage/app/public/shared-documents/{documentId} に保存されます。
     * 返却されるURL（asset('storage/' . $path)）に直接アクセスすることで画像を取得できます。
     * そのため、画像取得用のAPIエンドポイントは不要です。
     * 
     * フロントエンドでは、返却された url をそのまま <img src={url}> で使用できます。
     * 
     * 注意: シンボリックリンク（php artisan storage:link）が作成されている必要があります。
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

        $url = asset('storage/' . $path);

        return response()->json([
            'url' => $url,
            'path' => $path,
        ], 201);
    }

    /**
     * 共有ドキュメントの画像を削除
     * 
     * 画像ファイルを削除します。
     * 他のドキュメントで使われている画像は削除されません。
     */
    public function deleteImage(Request $request, string $roomId, string $filename)
    {
        $document = SharedDocument::where('room_id', $roomId)->firstOrFail();
        
        $deleted = $this->deleteImageFile($document->id, $filename);
        
        if (!$deleted) {
            return response()->json([
                'message' => '画像の削除に失敗しました。ファイルが存在しないか、他のドキュメントで使用されています。',
            ], 404);
        }

        return response()->json([
            'message' => '画像を削除しました。',
        ], 200);
    }

    /**
     * contentから削除された画像ファイルを削除
     *
     * @param int $documentId
     * @param string $oldContent
     * @param string $newContent
     * @return void
     */
    private function deleteUnusedImages(int $documentId, string $oldContent, string $newContent): void
    {
        // 古いcontentから画像URLを抽出
        $oldImageUrls = $this->extractImageUrls($oldContent);
        
        // 新しいcontentから画像URLを抽出
        $newImageUrls = $this->extractImageUrls($newContent);
        
        // 削除された画像URLを特定
        $deletedUrls = array_diff($oldImageUrls, $newImageUrls);
        
        // 削除された画像ファイルを削除
        foreach ($deletedUrls as $url) {
            // 共有ドキュメントフォルダ（shared-documents/{documentId}/）内の画像のみを対象
            if (preg_match('/\/storage\/shared-documents\/' . preg_quote($documentId, '/') . '\/([^\/]+)$/', $url, $fileMatch)) {
                $filename = $fileMatch[1];
                $filePath = "shared-documents/{$documentId}/{$filename}";
                
                // ファイルが存在し、他のドキュメントで使われていない場合のみ削除
                if (Storage::disk('public')->exists($filePath)) {
                    // 他のドキュメントで使われているかチェック
                    $isUsedInOtherDocuments = SharedDocument::where('id', '!=', $documentId)
                        ->where('content', 'like', '%' . $filename . '%')
                        ->exists();
                    
                    if (!$isUsedInOtherDocuments) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
            }
        }
    }

    /**
     * contentから画像URLを抽出
     *
     * @param string $content
     * @return array
     */
    private function extractImageUrls(string $content): array
    {
        $urls = [];
        // Markdown形式の画像URLを抽出: ![alt](url)
        preg_match_all('/!\[.*?\]\((.*?)\)/', $content, $matches);
        
        foreach ($matches[1] as $url) {
            $urls[] = $url;
        }
        
        return $urls;
    }

    /**
     * 画像ファイルを削除
     *
     * @param int $documentId
     * @param string $filename
     * @return bool
     */
    private function deleteImageFile(int $documentId, string $filename): bool
    {
        $filePath = "shared-documents/{$documentId}/{$filename}";
        
        // ファイルが存在しない場合はfalseを返す
        if (!Storage::disk('public')->exists($filePath)) {
            return false;
        }
        
        // 他のドキュメントで使われているかチェック
        $isUsedInOtherDocuments = SharedDocument::where('id', '!=', $documentId)
            ->where('content', 'like', '%' . $filename . '%')
            ->exists();
        
        // 他のドキュメントで使われている場合は削除しない
        if ($isUsedInOtherDocuments) {
            return false;
        }
        
        // ファイルを削除
        return Storage::disk('public')->delete($filePath);
    }

    /**
     * ロック取得
     * POST /api/documents/{roomId}/lock
     * 
     * 認証: 不要（セッションIDで識別）
     */
    public function lock(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();

        // 既存のロックを確認（有効期限切れのロックは無視）
        $existingLock = DocumentLock::where('room_id', $roomId)
            ->where('expires_at', '>', now())
            ->first();

        // 有効なロックが存在し、かつ自分のロックでない場合
        if ($existingLock && $existingLock->session_id !== $sessionId) {
            return response()->json([
                'success' => false,
                'error' => 'already_locked',
                'message' => '他のユーザーが編集中です',
                'locked_at' => $existingLock->locked_at->toIso8601String(),
            ], 409);
        }

        // 既存のロックを削除（自分のロックの場合も更新）
        DocumentLock::where('room_id', $roomId)->delete();

        // 新しいロックを作成
        $lock = DocumentLock::create([
            'room_id' => $roomId,
            'session_id' => $sessionId,
            'locked_at' => now(),
            'expires_at' => now()->addSeconds(90), // 1.5分 = 90秒
        ]);

        // WebSocketで通知（全員に送信）
        if (config('broadcasting.default') !== 'null') {
            try {
                $event = new DocumentLocked($roomId, $sessionId);
                $pendingBroadcast = broadcast($event);
                unset($pendingBroadcast);
            } catch (BroadcastException $e) {
                Log::error('Failed to broadcast document lock: ' . $e->getMessage());
            } catch (\Exception $e) {
                Log::error('Failed to broadcast document lock: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'locked_at' => $lock->locked_at->toIso8601String(),
            'expires_at' => $lock->expires_at->toIso8601String(),
        ], 200);
    }

    /**
     * ロック解放
     * DELETE /api/documents/{roomId}/lock
     * 
     * 認証: 不要（セッションIDで識別）
     */
    public function unlock(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();

        $lock = DocumentLock::where('room_id', $roomId)->first();

        // ロックが存在しない場合
        if (!$lock) {
            return response()->json([
                'success' => true,
                'message' => 'ロックは既に解放されています。',
            ], 200);
        }

        // 自分のロックでない場合
        if ($lock->session_id !== $sessionId) {
            return response()->json([
                'success' => false,
                'error' => 'not_locked_by_user',
                'message' => 'あなたはロックを保持していません',
            ], 403);
        }

        // ロックを削除
        $lock->delete();

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

        return response()->json([
            'success' => true,
            'message' => 'ロックを解放しました。',
        ], 200);
    }

    /**
     * ロック状態確認
     * GET /api/documents/{roomId}/lock
     * 
     * 認証: 不要
     */
    public function getLockStatus(string $roomId)
    {
        $lock = DocumentLock::where('room_id', $roomId)
            ->where('expires_at', '>', now())
            ->first();

        if (!$lock) {
            return response()->json([
                'is_locked' => false,
            ], 200);
        }

        return response()->json([
            'is_locked' => true,
            'locked_at' => $lock->locked_at->toIso8601String(),
            'expires_at' => $lock->expires_at->toIso8601String(),
        ], 200);
    }

    /**
     * ロック更新（ハートビート）
     * PUT /api/documents/{roomId}/lock
     * 
     * 認証: 不要（セッションIDで識別）
     */
    public function updateLock(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();

        $lock = DocumentLock::where('room_id', $roomId)->first();

        // ロックが存在しない場合
        if (!$lock) {
            return response()->json([
                'success' => false,
                'error' => 'lock_not_found',
                'message' => 'ロックが見つかりません。',
            ], 404);
        }

        // 自分のロックでない場合
        if ($lock->session_id !== $sessionId) {
            return response()->json([
                'success' => false,
                'error' => 'not_locked_by_user',
                'message' => 'あなたはロックを保持していません',
            ], 403);
        }

        // ロックを延長（1秒延長）
        $lock->extend();

        return response()->json([
            'success' => true,
            'expires_at' => $lock->expires_at->toIso8601String(),
        ], 200);
    }
}