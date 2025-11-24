<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),  // ユーザーも自動作成
            'title' => fake()->sentence(),  // ランダムなタイトル
            'content' => fake()->paragraphs(3, true),  // 3段落の本文
            'status' => 'published',  // デフォルトは公開
        ];
    }

    /**
     * 下書き状態の投稿を作成
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * 公開状態の投稿を作成（明示的に）
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }
}