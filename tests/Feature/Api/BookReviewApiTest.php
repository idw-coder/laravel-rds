<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookReview;
use App\Models\User;
use App\Services\BookSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BookReviewApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レビュー一覧取得テスト
     */
    public function test_can_get_book_reviews_list(): void
    {
        $user = User::factory()->create();
        $book = Book::create([
            'isbn' => '9784802615112',
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);
        BookReview::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'review' => 'テストレビュー',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/book-reviews');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.review', 'テストレビュー');
    }

    /**
     * 下書きレビューは一覧に表示されないテスト
     */
    public function test_draft_reviews_are_not_shown_in_list(): void
    {
        $user = User::factory()->create();
        $book = Book::create([
            'isbn' => '9784802615112',
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);
        BookReview::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'review' => '下書きレビュー',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/book-reviews');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * レビュー作成テスト（外部APIモック使用）
     */
    public function test_can_create_book_review_with_mocked_api(): void
    {
        $user = User::factory()->create();

        // 外部APIをモック
        $mockService = Mockery::mock(BookSearchService::class);
        $mockService->shouldReceive('searchByIsbn')
            ->with('9784873116860')
            ->once()
            ->andReturn([
                'isbn' => '9784873116860',
                'title' => 'Web API: The Good Parts',
                'author' => '水野貴明',
                'publisher' => 'オライリージャパン',
                'price' => 2376,
                'published_date' => '2014-11-21',
                'cover_url' => 'https://example.com/cover.jpg',
                'description' => 'API設計の基本',
            ]);
        $this->app->instance(BookSearchService::class, $mockService);

        $response = $this->actingAs($user)->postJson('/api/book-reviews', [
            'isbn' => '9784873116860',
            'rating' => 5,
            'review' => 'API設計の基本が学べる良書',
            'status' => 'published',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('review', 'API設計の基本が学べる良書');

        $this->assertDatabaseHas('books', ['isbn' => '9784873116860']);
        $this->assertDatabaseHas('book_reviews', ['review' => 'API設計の基本が学べる良書']);
    }

    /**
     * 認証なしでレビュー作成できないテスト
     */
    public function test_cannot_create_review_without_auth(): void
    {
        $response = $this->postJson('/api/book-reviews', [
            'isbn' => '9784873116860',
            'rating' => 5,
            'review' => 'テストレビュー',
        ]);

        $response->assertStatus(401);
    }
}