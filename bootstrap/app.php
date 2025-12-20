<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Web用CORSミドルウェアを追加
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    
        // API用CORSミドルウェアを追加
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // APIルートでセッションを使用するため、StartSessionミドルウェアを追加
        // （ロック機能でセッションIDを使用するため）
        $middleware->api(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        // Sanctumの権限チェックミドルウェアを追加
        // CheckAbilities: リクエストされた全ての権限（abilities）がトークンに含まれているかチェック
        // CheckForAnyAbility: リクエストされた権限のうち、少なくとも1つがトークンに含まれているかチェック
        // ※ auth:sanctum は各ルートで個別に指定する
        $middleware->api(append: [
            // \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            // \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);

        // ミドルウェアのエイリアス登録、middleware('admin')で使用
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
