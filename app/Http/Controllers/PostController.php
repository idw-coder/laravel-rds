<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // 一覧取得
    public function index()
    {
        return Post::all();
    }

    // 作成
    public function store(Request $request)
    {
        $post = Post::create($request->all());
        return response()->json($post, 201);
    }

    // 詳細取得
    public function show(Post $post)
    {
        return $post;
    }

    // 更新
    public function update(Request $request, Post $post)
    {
        $post->update($request->all());
        return response()->json($post);
    }

    // 削除
    public function destroy(Post $post)
    {
        $post->delete();
        return response()->json(null, 204);
    }
}