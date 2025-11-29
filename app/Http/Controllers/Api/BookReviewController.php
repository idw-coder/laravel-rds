<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookReview;
use App\Services\BookSearchService;
use Illuminate\Http\Request;

class BookReviewController extends Controller
{
    public function __construct(
        private BookSearchService $bookSearchService
    ) {}

    /**
     * レビュー一覧取得
     */
    public function index()
    {
        $reviews = BookReview::with(['user', 'book'])
            ->where('status', 'published')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * レビュー詳細取得
     */
    public function show(BookReview $bookReview)
    {
        return response()->json(
            $bookReview->load(['user', 'book'])
        );
    }

    /**
     * レビュー作成
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'isbn' => 'required|string|size:13',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:2000',
            'status' => 'in:draft,published',
        ]);

        // 書籍をDBから検索、なければ外部APIで取得して保存
        $book = Book::where('isbn', $validated['isbn'])->first();

        if (!$book) {
            $bookData = $this->bookSearchService->searchByIsbn($validated['isbn']);

            if (!$bookData) {
                return response()->json([
                    'message' => '書籍情報が見つかりませんでした'
                ], 404);
            }

            $book = Book::create($bookData);
        }

        $review = BookReview::create([
            'user_id' => $request->user()->id,
            'book_id' => $book->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'status' => $validated['status'] ?? 'draft',
        ]);

        return response()->json(
            $review->load(['user', 'book']),
            201
        );
    }

    /**
     * レビュー更新
     */
    public function update(Request $request, BookReview $bookReview)
    {
        if ($bookReview->user_id !== $request->user()->id) {
            return response()->json(['message' => '権限がありません'], 403);
        }

        $validated = $request->validate([
            'rating' => 'integer|min:1|max:5',
            'review' => 'string|max:2000',
            'status' => 'in:draft,published',
        ]);

        $bookReview->update($validated);

        return response()->json(
            $bookReview->load(['user', 'book'])
        );
    }

    /**
     * レビュー削除
     */
    public function destroy(Request $request, BookReview $bookReview)
    {
        if ($bookReview->user_id !== $request->user()->id) {
            return response()->json(['message' => '権限がありません'], 403);
        }

        $bookReview->delete();

        return response()->json(null, 204);
    }
}