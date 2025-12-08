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

    /**
     * 指定日数以上更新されていない下書きを取得するクエリスコープ
     * 
     * 使い方: Post::oldDrafts(30)->get()
     * 
     * クエリスコープ：再利用可能なクエリ条件をメソッドとして定義する機能
     * scope + メソッド名（先頭大文字）で定義し、呼び出し時は小文字で使用
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query クエリビルダー
     * @param int $days 経過日数（デフォルト30日）
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOldDrafts($query, int $days = 30)
    {
        return $query->where('status', 'draft')
                     ->where('updated_at', '<', now()->subDays($days));
    }
}