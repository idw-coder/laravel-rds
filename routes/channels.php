<?php

/*
    Laravel のブロードキャスト（WebSocket）で使うチャンネル認証を定義するファイル
*/

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
