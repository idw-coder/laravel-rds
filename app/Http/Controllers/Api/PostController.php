<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class PostController extends Controller
{
    protected PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    private function authorizePost(Request $request)
    {
        $roleNames = $request->user()->roles->pluck('name');

        if (!$roleNames->contains('admin') && !$roleNames->contains('paid')) {
            return response()->json(['message' => 'この操作を行う権限がありません'], 403);
        }

        return null;
    }

    private function getCurrentUser(Request $request)
    {
        $user = $request->user();
        
        if (!$user && $request->hasHeader('Authorization')) {
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            $personalAccessToken = PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            }
        }
        
        // rolesをロードして返す
        return $user?->load('roles');
    }
    
    public function index(Request $request)
    {
        $currentUser = $this->getCurrentUser($request);
        return $this->postService->getPostsList($currentUser);
    }

    public function store(Request $request)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }

        $post = $this->postService->createPost($request->user(), $request->all());
        return response()->json($post->load('user'), 201);
    }

    public function show(Request $request, Post $post)
    {
        $currentUser = $this->getCurrentUser($request);
        
        if (!$this->postService->canViewPost($post, $currentUser)) {
            abort(403, 'この投稿を閲覧する権限がありません。');
        }
        
        return $post->load('user');
    }

    public function update(Request $request, Post $post)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }

        $user = $request->user()->load('roles');
        if (!$this->postService->canManagePost($post, $user)) {
            abort(403, '更新権限がありません。');
        }

        $updatedPost = $this->postService->updatePost($post, $request->all());
        return response()->json($updatedPost);
    }

    public function destroy(Request $request, Post $post)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }

        $user = $request->user()->load('roles');
        if (!$this->postService->canManagePost($post, $user)) {
            abort(403, '削除権限がありません。');
        }

        $this->postService->deletePost($post);
        return response()->json(null, 204);
    }

    /**
     * 投稿用画像をアップロード（新規作成時用：一時フォルダに保存）
     * 
     * 画像は storage/app/public/posts/temp に一時保存されます。
     * 投稿作成時に posts/{postId}/ に移動されます。
     * 返却されるURL（asset('storage/' . $path)）に直接アクセスすることで画像を取得できます。
     * 
     * フロントエンドでは、返却された url をそのまま <img src={url}> で使用できます。
     * 
     * 注意: シンボリックリンク（php artisan storage:link）が作成されている必要があります。
     */
    public function uploadImage(Request $request)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }

        $validated = $request->validate([
            'image' => 'required|file|image|max:5120', // 5MB以下
        ]);

        $file = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('posts/temp', $filename, 'public');

        $url = asset('storage/' . $path);

        return response()->json([
            'url' => $url,
            'path' => $path,
        ], 201);
    }

    /**
     * 既存投稿への画像をアップロード
     * 
     * 画像は storage/app/public/posts/{postId} に保存されます。
     * 返却されるURL（asset('storage/' . $path)）に直接アクセスすることで画像を取得できます。
     * 
     * フロントエンドでは、返却された url をそのまま <img src={url}> で使用できます。
     * 
     * 注意: シンボリックリンク（php artisan storage:link）が作成されている必要があります。
     */
    public function uploadImageToPost(Request $request, Post $post)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }

        $user = $request->user()->load('roles');
        if (!$this->postService->canManagePost($post, $user)) {
            abort(403, 'この投稿に画像を追加する権限がありません。');
        }

        $validated = $request->validate([
            'image' => 'required|file|image|max:5120', // 5MB以下
        ]);

        $file = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("posts/{$post->id}", $filename, 'public');

        $url = asset('storage/' . $path);

        return response()->json([
            'url' => $url,
            'path' => $path,
        ], 201);
    }
}