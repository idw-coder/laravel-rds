<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase; // テスト後データベースをリセット
    /**
     * ログインテスト
     */
    public function test_succeed_login(): void
    {
        // 1. テストユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. ログインAPIを叩く
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // 3. 検証
        $response->assertStatus(200); // 200 OKか？
        $response->assertJsonStructure(['authToken', 'user']); // authTokenとuserが返されるか？
    }

    /**
     * ログイン失敗テスト
     */
    public function test_failed_login(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword', // 間違ったパスワード
        ]);

        $response->assertStatus(401); // 401 Unauthorizedか？
        $response->assertJsonStructure(['message']); // messageが返されるか？
    }

    /**
     * adminロールを持つユーザーが作成できる
     */
    public function test_create_user_with_admin_role(): void
    {
        // adminロール付きユーザーを作成
        $user = User::factory()->withRole('admin')->create();

        // 検証
        $this->assertTrue($user->roles->pluck('name')->contains('admin'));
    }

    /**
     * paidロールを持つユーザーが作成できる
     */
    public function test_create_user_with_paid_role(): void
    {
        // paidロール付きユーザーを作成
        $user = User::factory()->withRole('paid')->create();

        // 検証
        $this->assertTrue($user->roles->pluck('name')->contains('paid'));
    }

    /**
     * ロールなしユーザーが作成できる
     */
    public function test_create_user_without_role(): void
    {
        // 通常ユーザーを作成
        $user = User::factory()->create();

        // 検証
        $this->assertCount(0, $user->roles);
    }

    /**
     * ログイン済みユーザーは自分の情報を取得できる
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // 認証してリクエスト
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        // 検証
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * 未ログインユーザーは401エラー
     */
    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        // 認証なしでリクエスト
        $response = $this->getJson('/api/user');

        // 検証
        $response->assertStatus(401);
    }
}
