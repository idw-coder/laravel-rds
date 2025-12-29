<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Log;
use App\Models\SharedDocument;

class SharedDocumentRepository
{
    public function findByRoomId(string $roomId): ?SharedDocument
    {
        return SharedDocument::where('room_id', $roomId)->first();
    }

    public function findOrCreateByRoomId(string $roomId): SharedDocument
    {
        return SharedDocument::firstOrCreate(
            ['room_id' => $roomId], 
            ['content' => '']
        );
    }

    public function updateContent(string $roomId, string $content): SharedDocument
    {
        $document = $this->findOrCreateByRoomId($roomId);
        $document->content = $content;
        /**
         * save(): データベースに保存（更新も含む）
         */
        $document->save();
        Log::debug('Document content updated for room: ' . $roomId);
        return $document;
    }
}