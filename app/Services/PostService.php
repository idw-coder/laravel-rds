<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class PostService
{
    /**
     * ユーザーがadminロールを持っているかチェック
     *
     * @param User|null $user
     * @return bool
     */
    private function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $user->roles->contains('name', 'admin');
    }

    /**
     * 投稿一覧を取得（公開済み + 自分の投稿、adminは全投稿）
     *
     * @param User|null $currentUser
     * @return Collection
     */
    public function getPostsList(?User $currentUser = null): Collection
    {
        $query = Post::with('user');

        // adminは全投稿を取得可能
        if ($this->isAdmin($currentUser)) {
            return $query->latest()->get();
        }

        return $query
            ->where(function ($q) use ($currentUser) {
                $q->where('status', 'published');
                
                if ($currentUser) {
                    $q->orWhere('user_id', $currentUser->id);
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
        $post = $user->posts()->create($data);
        
        // contentから一時フォルダの画像を検出して移動
        $this->moveTempImagesToPost($post, $data['content'] ?? '');
        
        return $post;
    }

    /**
     * content内の一時フォルダの画像を投稿フォルダに移動
     *
     * @param Post $post
     * @param string $content
     * @return void
     */
    private function moveTempImagesToPost(Post $post, string $content): void
    {
        // Markdown形式の画像URLを抽出: ![alt](url)
        preg_match_all('/!\[.*?\]\((.*?)\)/', $content, $matches);
        
        foreach ($matches[1] as $url) {
            // URLからパスを抽出: http://localhost/storage/posts/temp/filename.jpg
            if (preg_match('/\/storage\/posts\/temp\/([^\/]+)$/', $url, $fileMatch)) {
                $filename = $fileMatch[1];
                $tempPath = "posts/temp/{$filename}";
                $newPath = "posts/{$post->id}/{$filename}";
                
                // 一時フォルダにファイルが存在する場合のみ移動
                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->move($tempPath, $newPath);
                    
                    // content内のURLも更新
                    $oldUrl = $url;
                    $newUrl = asset('storage/' . $newPath);
                    $post->content = str_replace($oldUrl, $newUrl, $post->content);
                }
            }
        }
        
        // URLが更新された場合は保存
        if ($post->isDirty('content')) {
            $post->save();
        }
    }

    /**
     * 投稿の閲覧権限をチェック
     *
     * @param Post $post
     * @param User|null $currentUser
     * @return bool
     */
    public function canViewPost(Post $post, ?User $currentUser = null): bool
    {
        // 公開済みは誰でも閲覧可能
        if ($post->status === 'published') {
            return true;
        }

        // adminは全て閲覧可能
        if ($this->isAdmin($currentUser)) {
            return true;
        }

        // 下書きは自分のもののみ閲覧可能
        return $currentUser && $post->user_id === $currentUser->id;
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
        // 投稿に紐づく画像フォルダを削除
        $imageDir = "posts/{$post->id}";
        if (Storage::disk('public')->exists($imageDir)) {
            Storage::disk('public')->deleteDirectory($imageDir);
        }
        
        return $post->delete();
    }

    /**
     * 投稿の管理権限をチェック（所有者またはadmin）
     *
     * @param Post $post
     * @param User $user
     * @return bool
     */
    public function canManagePost(Post $post, User $user): bool
    {
        // adminは全投稿を管理可能
        if ($this->isAdmin($user)) {
            return true;
        }

        // 所有者は自分の投稿を管理可能
        return $post->user_id === $user->id;
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