<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // 一覧取得
    public function index()
    {
        return Post::with('user')->get(); // ユーザー情報も一緒に取得
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

    // 詳細取得
    public function show(Post $post)
    {
        // load('user'): 投稿にユーザー情報をロードして返す
        return $post->load('user');
    }

    // 更新
    public function update(Request $request, Post $post)
    {
        $post->update($request->all());
        // load('user'): 更新した投稿にユーザー情報をロードして返す
        return response()->json($post->load('user'));
    }

    // 削除
    public function destroy(Post $post)
    {
        $post->delete();
        return response()->json(null, 204);
    }
}