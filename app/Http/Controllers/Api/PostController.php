<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
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

    private function getCurrentUserId(Request $request): ?int
    {
        $user = $request->user();
        
        if (!$user && $request->hasHeader('Authorization')) {
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            $personalAccessToken = PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            }
        }
        
        return $user?->id;
    }
    
    public function index(Request $request)
    {
        $currentUserId = $this->getCurrentUserId($request);
        return $this->postService->getPostsList($currentUserId);
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
        $currentUserId = $this->getCurrentUserId($request);
        
        if (!$this->postService->canViewPost($post, $currentUserId)) {
            abort(403, 'この投稿を閲覧する権限がありません。');
        }
        
        return $post->load('user');
    }

    public function update(Request $request, Post $post)
    {
        if ($res = $this->authorizePost($request)) {
            return $res;
        }

        if (!$this->postService->isOwner($post, $request->user()->id)) {
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

        if (!$this->postService->isOwner($post, $request->user()->id)) {
            abort(403, '削除権限がありません。');
        }

        $this->postService->deletePost($post);
        return response()->json(null, 204);
    }
}