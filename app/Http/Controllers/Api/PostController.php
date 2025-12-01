<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

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
        // 認証不要のルートでも、トークンが送信されていればユーザーを取得できるようにする
        $user = $request->user();
        $personalAccessToken = null;
        
        // トークンから明示的にユーザーを取得（認証不要ルートの場合）
        if (!$user && $request->hasHeader('Authorization')) {
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            $personalAccessToken = PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            }
        }
        
        $currentUserId = $user?->id;

        $query = Post::with('user')
            ->where(function ($query) use ($currentUserId) {
                $query->where('status', 'published');
                
                if ($currentUserId) {
                    $query->orWhere('user_id', $currentUserId);
                }
            })
            ->latest();

        // SQLデバッグ（確認後はコメントアウト）
        // dd($query->toRawSql());


        // SQLをログに出力
        // Log::info([
        //     'file' => __FILE__,
        //     'sql' => $query->toRawSql(),
        // ]);

        return $query->get();
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
        // ログインしている場合はユーザーIDを取得、未ログインの場合はnull
        // 認証不要のルートでも、トークンが送信されていればユーザーを取得できるようにする
        $user = $request->user();
        
        // トークンから明示的にユーザーを取得（認証不要ルートの場合）
        if (!$user && $request->hasHeader('Authorization')) {
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            $personalAccessToken = PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            }
        }
        
        $currentUserId = $user?->id;
        
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