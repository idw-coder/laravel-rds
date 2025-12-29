<?php

namespace App\Services;

use App\Repositories\SharedDocumentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Repositories\DocumentLockRepository;
use App\Events\DocumentLocked;
use App\Events\DocumentUnlocked;
use App\Events\SharedDocumentUpdated;

class SharedDocumentService
{
    private SharedDocumentRepository $sharedDocumentRepository;
    private DocumentLockRepository $documentLockRepository;
    public function __construct(
        SharedDocumentRepository $sharedDocumentRepository, 
        DocumentLockRepository $documentLockRepository)
    {
        $this->sharedDocumentRepository = $sharedDocumentRepository;
        $this->documentLockRepository = $documentLockRepository;
    }

    public function updateContent(string $roomId, string $content): array
    {
        $document = $this->sharedDocumentRepository->findOrCreateByRoomId($roomId);
        $oldContent = $document->content ?? '';
        $newContent = $content ?? '';

        if ($newContent !== $oldContent && $document->id) {
            $this->cleanupDeletedImages($document->id, $oldContent, $newContent);
        }

        $document = $this->sharedDocumentRepository->updateContent($roomId, $newContent);
        broadcast(new SharedDocumentUpdated($roomId, $document->content));

        return [
            'room_id' => $document->room_id,
            'content' => $document->content,
        ];
    }

    public function uploadImage(string $roomId, UploadedFile $file): array
    {
        $document = $this->sharedDocumentRepository->findOrCreateByRoomId($roomId);
    
        $filename = $this->generateFilename($file);
        /**
         * storeAs()
         * @param string $path
         * @param string $name
         * @param string $disk
         * @return string ファイルのパス
         * ファイルを保存
         */
        $path = $file->storeAs(
            "shared-documents/{$document->id}",
            $filename,
            'public'
        );
        
        return [
            'url' => asset('storage/' . $path),
            'path' => $path,
        ];
    }
    
    private function generateFilename(UploadedFile $file): string
    {
        /**
         * getClientOriginalExtension()
         * @param UploadedFile $file
         * @return string ファイルの拡張子
         */
        return time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    }

    public function deleteImageFile(int $documentId, string $filename): bool
    {
        $filePath = "shared-documents/{$documentId}/{$filename}";

        if (!Storage::disk('public')->exists($filePath)) {
            return false;
        }

        /**
         * delete()
         * @param string $path
         * @return bool
         * ファイルを削除
         */
        return Storage::disk('public')->delete($filePath);
    }

    
    /**
     * 削除された画像をクリーンアップ
     */
    public function cleanupDeletedImages(int $documentId, string $oldContent, string $newContent): void
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


    // ============================================
    // ロック操作
    // ============================================
    public function getLockStatus(string $roomId, string $sessionId): array
    {
        $lock = $this->documentLockRepository->findByRoomId($roomId);
        return [
            'is_locked' => (bool) $lock,
            'is_my_lock' => $lock && $lock->session_id === $sessionId,
            'locked_at' => $lock?->locked_at->toIso8601String(),
        ];
    }

    public function lock(string $roomId, string $sessionId): array
    {
        return DB::transaction(function () use ($roomId, $sessionId) {
            $existingLock = $this->documentLockRepository->findByRoomIdWithRowLock($roomId);

            if ($existingLock && $existingLock->session_id !== $sessionId) {
                return [
                    'success' => false,
                    'error' => 'already_locked',
                    'message' => '他のユーザーが編集中です',
                    'locked_at' => $existingLock->locked_at->toIso8601String(),
                ];
            }

            $this->documentLockRepository->deleteByRoomId($roomId);

            $lock = $this->documentLockRepository->create([
                'room_id' => $roomId,
                'session_id' => $sessionId,
                'locked_at' => now(),
                'expires_at' => now()->addMinutes(1.5),
            ]);

            broadcast(new DocumentLocked($roomId, $sessionId, $lock->locked_at->toIso8601String()));

            return [
                'success' => true,
                'session_id' => $sessionId,
                'locked_at' => $lock->locked_at->toIso8601String(),
            ];
        });
    }

    public function unlock(string $roomId, string $sessionId): array
    {
        return DB::transaction(function () use ($roomId, $sessionId) {
            $lock = $this->documentLockRepository->findByRoomIdWithRowLock($roomId);

            if (!$lock) {
                return [
                    'success' => true,
                    'message' => 'ロックは既に解放されています。',
                ];
            }

            if ($lock->session_id !== $sessionId) {
                return [
                    'success' => false,
                    'error' => 'not_locked_by_user',
                    'message' => 'あなたはロックを保持していません',
                ];
            }

            $this->documentLockRepository->deleteByRoomId($roomId);
            broadcast(new DocumentUnlocked($roomId, $sessionId));

            return [
                'success' => true,
                'message' => 'ロックを解放しました。',
            ];
        });
    }
}