<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $paidUser  = User::where('email', 'paid@example.com')->first();
        $adminUser = User::where('email', 'admin@example.com')->first();

        // paidユーザーに 5 投稿
        if ($paidUser) {
            for ($i = 1; $i <= 5; $i++) {
                Post::create([
                    'user_id' => $paidUser->id,
                    'title'   => "Paidユーザーの投稿 {$i}",
                    'content' => "これはPaidユーザーの投稿 {$i} です。",
                    'status'  => 'published',
                ]);
            }
        }

        // adminユーザーに 3 投稿
        if ($adminUser) {
            for ($i = 1; $i <= 3; $i++) {
                Post::create([
                    'user_id' => $adminUser->id,
                    'title'   => "Adminユーザーの投稿 {$i}",
                    'content' => "これはAdminユーザーの投稿 {$i} です。",
                    'status'  => 'published',
                ]);
            }
        }
    }
}

