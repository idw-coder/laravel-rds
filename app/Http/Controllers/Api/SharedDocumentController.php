<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SharedDocumentService;
use App\Repositories\SharedDocumentRepository;

class SharedDocumentController extends Controller
{
    private SharedDocumentRepository $sharedDocumentRepository;
    private SharedDocumentService $sharedDocumentService;
    public function __construct(
        SharedDocumentRepository $sharedDocumentRepository, 
        SharedDocumentService $sharedDocumentService, 
        )
    {
        $this->sharedDocumentRepository = $sharedDocumentRepository;
        $this->sharedDocumentService = $sharedDocumentService;
    }

    public function show(string $roomId)
    {
        $document = $this->sharedDocumentRepository->findOrCreateByRoomId($roomId);

        return response()->json([
            'room_id' => $document->room_id,
            'content' => $document->content,
        ]);
    }

    public function update(Request $request, string $roomId)
    {
        $request->validate([
            'content' => 'nullable|string',
        ]);

        $result = $this->sharedDocumentService->updateContent($roomId, $request->input('content', ''));
        return response()->json($result);
    }

    // ============================================
    // 画像操作
    // ============================================
    public function uploadImage(Request $request, string $roomId)
    {
        $request->validate([
            'image' => 'required|file|image|max:5120', // 5MB以下
        ]);

        $result = $this->sharedDocumentService->uploadImage($roomId, $request->file('image'));
        return response()->json($result, 201);
    }

    public function deleteImage(string $roomId, string $filename)
    {
        $document = $this->sharedDocumentRepository->findByRoomId($roomId);

        if (!$document) {
            return response()->json([
                'message' => 'ドキュメントが存在しません。',
            ], 404);
        }

        if (!$this->sharedDocumentService->deleteImageFile($document->id, $filename)) {
            return response()->json([
                'message' => '画像の削除に失敗しました。ファイルが存在しないか、他のドキュメントで使用されています。',
            ], 404);
        }

        return response()->json(['message' => '画像を削除しました。']);
    }

    // ============================================
    // ロック操作
    // ============================================

    public function getLockStatus(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();
        $result = $this->sharedDocumentService->getLockStatus($roomId, $sessionId);
        return response()->json($result);
    }

    public function lock(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();

        $result = $this->sharedDocumentService->lock($roomId, $sessionId);
        $statusCode = $result['success'] ? 200 : ($result['error'] === 'already_locked' ? 409 : 400);
        return response()->json($result, $statusCode);
    }

    public function unlock(Request $request, string $roomId)
    {
        $sessionId = $request->session()->getId();

        $result = $this->sharedDocumentService->unlock($roomId, $sessionId);
        $statusCode = $result['success'] ? 200 : ($result['error'] === 'not_locked_by_user' ? 403 : 400);
        return response()->json($result, $statusCode);
    }
}
