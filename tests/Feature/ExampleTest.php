<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * API テストエンドポイントが正常に動作するかテスト
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/api/test');  // 存在するルートに変更

        $response->assertStatus(200);
        $response->assertJson(['message' => 'API is working']);
    }
}