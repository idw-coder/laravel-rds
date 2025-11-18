<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    // HasFactory - テストデータ作成用のファクトリー機能を有効化
    // SoftDeletes - 論理削除機能を有効化（deleted_atカラムを使用）
    use HasFactory, SoftDeletes;

    // $fillable - 一括代入可能なカラムを指定
    // Post::create(['title' => '...']) のように配列で一度に保存できる
    // セキュリティ対策: ここに指定したカラムのみ一括代入可能
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'status',
    ];

    /**
     * この投稿の所有者（ユーザー）を取得
     * 
     * PostモデルとUserモデルの多対1のリレーションシップを定義
     * 1つの投稿は1人のユーザーに属する
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}