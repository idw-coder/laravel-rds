<?php

namespace App\Repositories;

use App\Models\DocumentLock;
use Illuminate\Support\Facades\Log;

class DocumentLockRepository
{
    public function findByRoomId(string $roomId): ?DocumentLock
    {
        return DocumentLock::where('room_id', $roomId)->first();
    }

    public function findByRoomIdWithRowLock(string $roomId): ?DocumentLock
    {
        return DocumentLock::where('room_id', $roomId)

        /**
         * lockForUpdate(): SELECT ... FOR UPDATE を発行し、トランザクション終了まで行をロック
         */
        ->lockForUpdate()
        ->first();
    }
    
    public function create(array $data): DocumentLock
    {
        Log::debug('Document lock created for room: ' . $data['room_id']);
        return DocumentLock::create($data);
    }

    public function deleteByRoomId(string $roomId): void
    {
        DocumentLock::where('room_id', $roomId)->delete();
        Log::debug('Document lock deleted for room: ' . $roomId);
    }
}