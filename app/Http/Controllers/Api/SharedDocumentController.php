<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SharedDocument;
use Illuminate\Http\Request;

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
     */
    public function update(Request $request, string $roomId)
    {
        $request->validate([
            'content' => 'nullable|string',
        ]);

        $document = SharedDocument::updateOrCreate(
            ['room_id' => $roomId],
            ['content' => $request->input('content', '')]
        );

        return response()->json([
            'room_id' => $document->room_id,
            'content' => $document->content,
        ]);
    }
}