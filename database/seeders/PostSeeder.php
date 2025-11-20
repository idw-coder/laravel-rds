<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * 1~5行のランダムなcontentを生成
     */
    private function generateContent(int $lineCount, string $userName, int $postNumber, string $type = '投稿'): string
    {
        $lines = [];
        for ($i = 1; $i <= $lineCount; $i++) {
            $lines[] = "これは{$userName}の{$type} {$postNumber} の{$i}行目です。";
        }
        return implode("\n", $lines);
    }

    public function run(): void
    {
        $paidUser  = User::where('email', 'watanabe@example.com')->first();
        $paidUser2 = User::where('email', 'matsumoto@example.com')->first();
        $adminUser = User::where('email', 'admin@example.com')->first();

        // paidユーザー（わたなべ）に 5 投稿（公開）
        if ($paidUser) {
            for ($i = 1; $i <= 5; $i++) {
                $lineCount = rand(1, 5);
                Post::create([
                    'user_id' => $paidUser->id,
                    'title'   => "わたなべの投稿 {$i}",
                    'content' => $this->generateContent($lineCount, 'わたなべ', $i, '投稿'),
                    'status'  => 'published',
                ]);
            }
            // paidユーザー（わたなべ）に 3 下書き投稿
            for ($i = 1; $i <= 3; $i++) {
                $lineCount = rand(1, 5);
                Post::create([
                    'user_id' => $paidUser->id,
                    'title'   => "わたなべの下書き {$i}",
                    'content' => $this->generateContent($lineCount, 'わたなべ', $i, '下書き'),
                    'status'  => 'draft',
                ]);
            }
        }

        // paidユーザー2（まつもと）に 5 投稿（公開）
        if ($paidUser2) {
            for ($i = 1; $i <= 5; $i++) {
                $lineCount = rand(1, 5);
                Post::create([
                    'user_id' => $paidUser2->id,
                    'title'   => "まつもとの投稿 {$i}",
                    'content' => $this->generateContent($lineCount, 'まつもと', $i, '投稿'),
                    'status'  => 'published',
                ]);
            }
            // paidユーザー2（まつもと）に 3 下書き投稿
            for ($i = 1; $i <= 3; $i++) {
                $lineCount = rand(1, 5);
                Post::create([
                    'user_id' => $paidUser2->id,
                    'title'   => "まつもとの下書き {$i}",
                    'content' => $this->generateContent($lineCount, 'まつもと', $i, '下書き'),
                    'status'  => 'draft',
                ]);
            }
        }

        // adminユーザーに 3 投稿（公開）
        if ($adminUser) {
            for ($i = 1; $i <= 3; $i++) {
                $lineCount = rand(1, 5);
                Post::create([
                    'user_id' => $adminUser->id,
                    'title'   => "Adminユーザーの投稿 {$i}",
                    'content' => $this->generateContent($lineCount, 'Adminユーザー', $i, '投稿'),
                    'status'  => 'published',
                ]);
            }
            // adminユーザーに 2 下書き投稿
            for ($i = 1; $i <= 2; $i++) {
                $lineCount = rand(1, 5);
                Post::create([
                    'user_id' => $adminUser->id,
                    'title'   => "Adminユーザーの下書き {$i}",
                    'content' => $this->generateContent($lineCount, 'Adminユーザー', $i, '下書き'),
                    'status'  => 'draft',
                ]);
            }
        }
    }
}

