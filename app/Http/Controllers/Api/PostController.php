<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // 一覧取得
    public function index(Request $request)
    {
        // $request->user()で認証済みユーザーを取得できる理由:
        // 1. このルートは auth:sanctum ミドルウェアで保護されている（routes/api.php参照）
        // 2. リクエストヘッダーのBearerトークンが検証され、認証済みユーザーがリクエストにバインドされる
        // 3. 未認証の場合は401エラーが返されるため、ここでは常に認証済みユーザーが存在する
        $currentUserId = $request->user()->id;

        $posts = Post::with('user')
            ->where(function ($query) use ($currentUserId) {
                // 条件: 「公開」または「自分の投稿」
                $query->where('status', 'published')
                      ->orWhere('user_id', $currentUserId);
            })
            ->latest() // 新しい順に並べる（任意）
            ->get();

        return $posts;
    }

    // 作成
    public function store(Request $request)
    {
        // 認証済みユーザーに紐づけて投稿を作成
        // $request->user(): 認証済みのユーザーを取得
        // ->posts(): Userモデルのposts()リレーションを使用
        // ->create(): リレーション経由で投稿を作成（自動的にuser_idが設定される）
        $post = $request->user()->posts()->create($request->all());
        // load('user'): 作成した投稿にユーザー情報をロードして返す
        return response()->json($post->load('user'), 201);
    }

    // 詳細取得（ここも重要！）
    public function show(Request $request, Post $post)
    {
        // 「下書き」かつ「自分の投稿でない」場合は閲覧禁止 (403 Forbidden)
        if ($post->status !== 'published' && $post->user_id !== $request->user()->id) {
             abort(403, 'この投稿を閲覧する権限がありません。');
        }
        
        return $post->load('user');
    }

    // 更新
    public function update(Request $request, Post $post)
    {
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
         // 他人の投稿は削除禁止
        if ($post->user_id !== $request->user()->id) {
             abort(403, '削除権限がありません。');
        }

        $post->delete();
        return response()->json(null, 204);
    }
}