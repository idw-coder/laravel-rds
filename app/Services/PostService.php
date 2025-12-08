<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    /**
     * 投稿一覧を取得（公開済み + 自分の投稿）
     *
     * @param int|null $currentUserId
     * @return Collection
     */
    public function getPostsList(?int $currentUserId = null): Collection
    {
        return Post::with('user')
            ->where(function ($query) use ($currentUserId) {
                $query->where('status', 'published');
                
                if ($currentUserId) {
                    $query->orWhere('user_id', $currentUserId);
                }
            })
            ->latest()
            ->get();
    }

    /**
     * 投稿を作成
     *
     * @param User $user
     * @param array $data
     * @return Post
     */
    public function createPost(User $user, array $data): Post
    {
        return $user->posts()->create($data);
    }

    /**
     * 投稿の閲覧権限をチェック
     *
     * @param Post $post
     * @param int|null $currentUserId
     * @return bool
     */
    public function canViewPost(Post $post, ?int $currentUserId = null): bool
    {
        // 公開済みは誰でも閲覧可能
        if ($post->status === 'published') {
            return true;
        }

        // 下書きは自分のもののみ閲覧可能
        return $post->user_id === $currentUserId;
    }

    /**
     * 投稿を更新
     *
     * @param Post $post
     * @param array $data
     * @return Post
     */
    public function updatePost(Post $post, array $data): Post
    {
        $post->update($data);
        return $post->fresh('user');
    }

    /**
     * 投稿を削除
     *
     * @param Post $post
     * @return bool|null
     */
    public function deletePost(Post $post): ?bool
    {
        return $post->delete();
    }

    /**
     * 投稿の所有者かチェック
     *
     * @param Post $post
     * @param int $userId
     * @return bool
     */
    public function isOwner(Post $post, int $userId): bool
    {
        return $post->user_id === $userId;
    }

    /**
     * 指定日数以上更新されていない下書きを取得
     *
     * @param int $days
     * @return Collection
     */
    public function getOldDrafts(int $days = 30): Collection
    {
        return Post::oldDrafts($days)->with('user')->get();
    }

    /**
     * 古い下書きを削除（ソフトデリート）
     *
     * @param int $days
     * @return int 削除件数
     */
    public function deleteOldDrafts(int $days = 30): int
    {
        return Post::oldDrafts($days)->delete();
    }
}