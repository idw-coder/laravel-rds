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

/**
 * ログイン（認証不要）
 * 
 * ルート名: login
 * URI: POST /api/login
 * HTTPメソッド: POST
 * 
 * [AuthController::class, 'login']
 *  - コントローラークラス: App\Http\Controllers\Api\AuthController
 *  - コントローラーメソッド: login()
 * 
 * 引数:
 *   - Request $request: リクエストオブジェクト
 *     - email (string, required): ユーザーのメールアドレス
 *     - password (string, required): ユーザーのパスワード
 * 
 * 処理内容:
 *   1. メールアドレスとパスワードでバリデーション
 *   2. Googleログイン用アカウントの場合はエラーを返す
 *   3. メール・パスワードで認証を試行
 *   4. 認証成功時、Sanctumトークンを発行
 * 
 * 返り値:
 *   - 成功時 (200): {'token': string, 'user': User}
 *   - バリデーションエラー (422): {'message': string, 'errors': array}
 *   - 認証失敗 (401): {'message': string}
 *   - サーバーエラー (500): {'message': string}
 */
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Google OAuth（認証不要）
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [UserController::class, 'show']);
    Route::put('/profile', [UserController::class, 'update']);
    
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
    Route::apiResource('posts', PostController::class);
});