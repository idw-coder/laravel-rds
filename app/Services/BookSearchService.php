<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookSearchService
{
    /**
     * ISBNから書籍情報を取得（楽天 → Google の優先順）
     */
    public function searchByIsbn(string $isbn): ?array
    {
        // 1. 楽天ブックスAPIで検索
        $result = $this->searchRakuten($isbn);

        if ($result) {
            return $result;
        }

        // 2. 楽天で見つからなければGoogle Books APIで検索
        return $this->searchGoogle($isbn);
    }

    /**
     * 楽天ブックスAPI
     */
    private function searchRakuten(string $isbn): ?array
    {
        $appId = config('services.rakuten_books_app_id.app_id');

        if (!$appId) {
            Log::warning('Rakuten App ID is not configured');
            return null;
        }

        try {
            $response = Http::get('https://app.rakuten.co.jp/services/api/BooksBook/Search/20170404', [
                'applicationId' => $appId,
                'isbn' => $isbn,
            ]);

            if ($response->successful() && $response->json('count') > 0) {
                $item = $response->json('Items.0.Item');

                return [
                    'isbn' => $item['isbn'],
                    'title' => $item['title'],
                    'author' => $item['author'],
                    'publisher' => $item['publisherName'] ?? null,
                    'price' => $item['itemPrice'] ?? null,
                    'published_date' => $this->parseRakutenDate($item['salesDate'] ?? null),
                    'cover_url' => $item['largeImageUrl'] ?? null,
                    'description' => $item['itemCaption'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Rakuten API error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Google Books API
     */
    private function searchGoogle(string $isbn): ?array
    {
        try {
            $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'isbn:' . $isbn,
            ]);

            if ($response->successful() && $response->json('totalItems') > 0) {
                $volume = $response->json('items.0.volumeInfo');

                return [
                    'isbn' => $isbn,
                    'title' => $volume['title'] ?? null,
                    'author' => isset($volume['authors']) ? implode(', ', $volume['authors']) : null,
                    'publisher' => null,
                    'price' => null,
                    'published_date' => $this->parseGoogleDate($volume['publishedDate'] ?? null),
                    'cover_url' => $volume['imageLinks']['thumbnail'] ?? null,
                    'description' => $volume['searchInfo']['textSnippet'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Books API error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * 楽天の日付形式をパース（例: "2025年07月18日頃"）
     */
    private function parseRakutenDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        if (preg_match('/(\d{4})年(\d{2})月(\d{2})日/', $date, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }

        return null;
    }

    /**
     * Googleの日付形式をパース（例: "2025-07-18"）
     */
    private function parseGoogleDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        // すでにY-m-d形式ならそのまま返す
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Y-m形式の場合は01日を追加
        if (preg_match('/^\d{4}-\d{2}$/', $date)) {
            return $date . '-01';
        }

        // Y形式の場合は01月01日を追加
        if (preg_match('/^\d{4}$/', $date)) {
            return $date . '-01-01';
        }

        return null;
    }
}