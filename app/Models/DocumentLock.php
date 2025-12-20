<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLock extends Model
{
    protected $fillable = [
        'room_id',
        'session_id', // 未認証ユーザーでも動作するように、セッションIDで識別
        'locked_at',
        'expires_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];


    /**
     * ロックが有効かどうかを判定
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * ロックを延長する（30秒延長）
     * 
     * @return void
     */
    public function extend(): void
    {
        $this->expires_at = now()->addSeconds(30); // 30秒（リアルタイム性を重視）
        $this->save();
    }
}
