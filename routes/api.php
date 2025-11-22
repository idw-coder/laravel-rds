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
 * 投稿リソース（RESTful API）
 * apiResource: RESTful APIのルートを自動生成（GET /posts, GET /posts/{id}, POST /posts, PUT /posts/{id}, DELETE /posts/{id}）
 * 認証不要: 一覧・詳細取得のみ
 */
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