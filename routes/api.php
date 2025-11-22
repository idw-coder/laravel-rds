<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\UserController;

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
    
    /**
     * APIリソースルート（RESTful API用）
     * 
     * メソッド: Route::apiResource()
     *  - LaravelのRESTful APIリソースルートを自動的に定義するメソッド
     *   - 'posts' (string): リソース名（URIのパス名として使用）
     *   - PostController::class (string): コントローラークラス
     *     - クラス: App\Http\Controllers\Api\PostController
     * 
     *   1. GET    /api/posts    
     * 自動生成されるルート:       → PostController::index()
     *   2. POST   /api/posts           → PostController::store()
     *   3. GET    /api/posts/{post}    → PostController::show()
     *   4. PUT    /api/posts/{post}    → PostController::update()
     *   5. PATCH  /api/posts/{post}    → PostController::update()
     *   6. DELETE /api/posts/{post}    → PostController::destroy()
     */
// 認証不要で投稿一覧と詳細を取得できるようにする
Route::apiResource('posts', PostController::class)->only(['index', 'show']);

// 認証が必要なルート、Sanctum 認証でトークンが有効な場合はルート処理、無効な場合は401エラー
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [UserController::class, 'show']);
    Route::put('/profile', [UserController::class, 'update']);

    Route::apiResource('posts', PostController::class)->only(['store', 'update', 'destroy']);
});