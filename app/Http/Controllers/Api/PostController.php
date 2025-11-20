<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;


class PostController extends Controller
{
    // 投稿の権限を確認するメソッド
    private function authorizePost(Request $request)
    {
        $roleNames = $request->user()->roles->pluck('name');

        if (!$roleNames->contains('admin') && !$roleNames->contains('paid')) {
            return response()->json(['message' => 'この操作を行う権限がありません'], 403);
        }

        return null;
    }
    
    // 一覧取得
    public function index(Request $request)
    {
        // ログインしている場合はユーザーIDを取得、未ログインの場合はnull
        // $request->user()は認証済みユーザーを返すが、未認証の場合はnullを返す
        $currentUserId = $request->user()->id ?? null;

        $posts = Post::with('user') // ユーザー情報も一緒に取得（N+1問題の回避）
            ->when($currentUserId, function ($query) use ($currentUserId) {
                // ログイン時: 公開記事 + 自分の投稿（下書き含む）を表示
                $query->where(function ($q) use ($currentUserId) {
                    $q->where('status', 'published')  // 公開記事
                      ->orWhere('user_id', $currentUserId); // または自分の投稿（下書きも含む）
                });
            }, function ($query) {
                // 未ログイン時: 公開記事のみを表示
                $query->where('status', 'published');
            })
            ->latest() // 新しい順に並べる（created_at DESC）
            ->get();

        return $posts;
    }

    // 作成
    public function store(Request $request)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }
        // 認証済みユーザーに紐づけて投稿を作成
        // $request->user(): 認証済みのユーザーを取得
        // ->posts(): Userモデルのposts()リレーションを使用
        // ->create(): リレーション経由で投稿を作成（自動的にuser_idが設定される）
        $post = $request->user()->posts()->create($request->all());
        // load('user'): 作成した投稿にユーザー情報をロードして返す
        return response()->json($post->load('user'), 201);
    }

    // 詳細取得
    public function show(Request $request, Post $post)
    {
        $currentUserId = $request->user()->id ?? null;
        // 「下書き」かつ「自分の投稿でない」場合は閲覧禁止 (403 Forbidden)
        if ($post->status !== 'published' && $post->user_id !== $currentUserId) {
             abort(403, 'この投稿を閲覧する権限がありません。');
        }
        
        return $post->load('user');
    }

    // 更新
    public function update(Request $request, Post $post)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }
        // 他人の投稿は更新禁止
        if ($post->user_id !== $request->user()->id) {
            abort(403, '更新権限がありません。');
        }

        $post->update($request->all());
        return response()->json($post->load('user'));
    }

    // 削除
    public function destroy(Request $request, Post $post)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }
         // 他人の投稿は削除禁止
        if ($post->user_id !== $request->user()->id) {
             abort(403, '削除権限がありません。');
        }

        $post->delete();
        return response()->json(null, 204);
    }
}