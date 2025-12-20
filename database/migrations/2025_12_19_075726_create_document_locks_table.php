<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_locks', function (Blueprint $table) {
            $table->id();
            $table->string('room_id'); // ドキュメントのルームID（shared_documents.room_idと対応）
            $table->string('session_id'); // セッションID（認証不要でユーザーを識別）
            $table->timestamp('locked_at')->useCurrent(); // ロック取得時刻
            $table->timestamp('expires_at'); // ロック有効期限（デフォルト: 1.5分後）
            $table->timestamps();

            // 1つのドキュメントに1つのロックのみ（room_idにユニーク制約）
            $table->unique('room_id');
            
            // expires_atにインデックス（タイムアウト検索用）
            $table->index('expires_at');
            
            // session_idにインデックス（セッション検索用）
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_locks');
    }
};
