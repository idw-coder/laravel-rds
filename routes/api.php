<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\BookReviewController;

// テスト用ルート
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

// ログイン
Route::post('/login', [AuthController::class, 'login'])->name('login');

// ユーザー登録
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Google OAuth（認証不要）
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// アバター画像取得（認証不要
Route::get('/avatar/{userId}', [UserController::class, 'getAvatar']);
    
/**
 * 投稿リソース（RESTful API）
 * apiResource: RESTful APIのルートを自動生成（GET /posts, GET /posts/{id}, POST /posts, PUT /posts/{id}, DELETE /posts/{id}）
 * 認証不要: 一覧・詳細取得のみ
 */
Route::apiResource('posts', PostController::class)->only(['index', 'show']);

// 書籍レビュー（認証不要：一覧・詳細）
Route::apiResource('book-reviews', BookReviewController::class)->only(['index', 'show']);

// 共同編集ドキュメント（認証不要）
Route::get('/documents/{roomId}', [App\Http\Controllers\Api\SharedDocumentController::class, 'show']);
Route::post('/documents/{roomId}', [App\Http\Controllers\Api\SharedDocumentController::class, 'update']);
Route::post('/documents/{roomId}/images', [App\Http\Controllers\Api\SharedDocumentController::class, 'uploadImage']);
Route::delete('/documents/{roomId}/images/{filename}', [App\Http\Controllers\Api\SharedDocumentController::class, 'deleteImage']);

// ドキュメントロック関連（認証不要：セッションIDで識別）
Route::get('/documents/{roomId}/lock', [App\Http\Controllers\Api\SharedDocumentController::class, 'getLockStatus']);
Route::post('/documents/{roomId}/lock', [App\Http\Controllers\Api\SharedDocumentController::class, 'lock']);
Route::delete('/documents/{roomId}/lock', [App\Http\Controllers\Api\SharedDocumentController::class, 'unlock']);
Route::put('/documents/{roomId}/lock', [App\Http\Controllers\Api\SharedDocumentController::class, 'updateLock']);

// 認証が必要なルート、Sanctum 認証でトークンが有効な場合はルート処理、無効な場合は401エラー
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [UserController::class, 'show']);
    Route::post('/profile', [UserController::class, 'update']);

    Route::apiResource('posts', PostController::class)->only(['store', 'update', 'destroy']);
    
    // 投稿用画像アップロード（新規作成時用：一時フォルダ）
    Route::post('/posts/images', [PostController::class, 'uploadImage']);
    
    // 既存投稿への画像アップロード
    Route::post('/posts/{post}/images', [PostController::class, 'uploadImageToPost']);
    
    // 投稿の画像削除
    Route::delete('/posts/{post}/images/{filename}', [PostController::class, 'deleteImage']);

    // 書籍レビュー（認証不要：作成・更新・削除）
    Route::apiResource('book-reviews', BookReviewController::class)->only(['store', 'update', 'destroy']);
});

/**
 * 管理者専用ルート（認証 + adminロール必須）
 */
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin') // グループ内のすべてのルートに '/admin' プレフィックスを追加
    ->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']); // ユーザー一覧取得
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']); // ユーザー削除（ソフトデリート）
        Route::post('/users/{id}/restore', [AdminUserController::class, 'restore']); // ユーザー復元
});