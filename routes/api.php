<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;

// ログインは認証不要
Route::post('/login', [AuthController::class, 'login']);

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::apiResource('posts', PostController::class);
});