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
        // contentが更新される場合、削除された画像ファイルを削除
        if (isset($data['content']) && $data['content'] !== $post->content) {
            $this->deleteUnusedImages($post, $data['content']);
        }
        
        $post->update($data);
        return $post->fresh('user');
    }

    /**
     * contentから削除された画像ファイルを削除
     *
     * @param Post $post
     * @param string $newContent
     * @return void
     */
    private function deleteUnusedImages(Post $post, string $newContent): void
    {
        // 古いcontentから画像URLを抽出
        $oldImageUrls = $this->extractImageUrls($post->content);
        
        // 新しいcontentから画像URLを抽出
        $newImageUrls = $this->extractImageUrls($newContent);
        
        // 削除された画像URLを特定
        $deletedUrls = array_diff($oldImageUrls, $newImageUrls);
        
        // 削除された画像ファイルを削除
        foreach ($deletedUrls as $url) {
            // 投稿フォルダ（posts/{postId}/）内の画像のみを対象
            if (preg_match('/\/storage\/posts\/' . $post->id . '\/([^\/]+)$/', $url, $fileMatch)) {
                $filename = $fileMatch[1];
                $filePath = "posts/{$post->id}/{$filename}";
                
                // ファイルが存在し、他の投稿で使われていない場合のみ削除
                if (Storage::disk('public')->exists($filePath)) {
                    // 他の投稿で使われているかチェック
                    $isUsedInOtherPosts = Post::where('id', '!=', $post->id)
                        ->where('content', 'like', '%' . $filename . '%')
                        ->exists();
                    
                    if (!$isUsedInOtherPosts) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
            }
        }
    }

    /**
     * contentから画像URLを抽出
     *
     * @param string $content
     * @return array
     */
    private function extractImageUrls(string $content): array
    {
        $urls = [];
        // Markdown形式の画像URLを抽出: ![alt](url)
        preg_match_all('/!\[.*?\]\((.*?)\)/', $content, $matches);
        
        foreach ($matches[1] as $url) {
            $urls[] = $url;
        }
        
        return $urls;
    }

    /**
     * 画像ファイルを削除
     *
     * @param Post $post
     * @param string $filename
     * @return bool
     */
    public function deleteImage(Post $post, string $filename): bool
    {
        $filePath = "posts/{$post->id}/{$filename}";
        
        // ファイルが存在しない場合はfalseを返す
        if (!Storage::disk('public')->exists($filePath)) {
            return false;
        }
        
        // 他の投稿で使われているかチェック
        $isUsedInOtherPosts = Post::where('id', '!=', $post->id)
            ->where('content', 'like', '%' . $filename . '%')
            ->exists();
        
        // 他の投稿で使われている場合は削除しない
        if ($isUsedInOtherPosts) {
            return false;
        }
        
        // ファイルを削除
        return Storage::disk('public')->delete($filePath);
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