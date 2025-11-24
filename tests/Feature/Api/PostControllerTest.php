<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Post;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 公開投稿は誰でも取得できる
     */
    public function test_guest_can_get_published_posts(): void
    {
        // 公開投稿を3つ作成
        Post::factory()->count(3)->published()->create();
        
        // 下書き投稿を2つ作成（これは表示されないはず）
        Post::factory()->count(2)->draft()->create();

        // 認証なしでリクエスト
        $response = $this->getJson('/api/posts');

        // 検証
        $response->assertStatus(200);
        $response->assertJsonCount(3);  // 公開投稿3つだけ返される
    }

    /**
     * ログインユーザーは自分の下書きも取得できる
     */
    public function test_user_can_get_own_draft_posts(): void
    {
        $user = User::factory()->create();
        
        // 自分の投稿（公開1つ、下書き2つ）
        Post::factory()->published()->create(['user_id' => $user->id]);
        Post::factory()->count(2)->draft()->create(['user_id' => $user->id]);
        
        // 他人の公開投稿
        Post::factory()->published()->create();

        // 認証してリクエスト
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/posts');

        // 検証: 自分の3つ + 他人の公開1つ = 4つ
        $response->assertStatus(200);
        $response->assertJsonCount(4);
    }
}