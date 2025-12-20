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
}
