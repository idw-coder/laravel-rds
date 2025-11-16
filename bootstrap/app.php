<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
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

        // Sanctumの権限チェックミドルウェアを追加
        // CheckAbilities: リクエストされた全ての権限（abilities）がトークンに含まれているかチェック
        // CheckForAnyAbility: リクエストされた権限のうち、少なくとも1つがトークンに含まれているかチェック
        // ※ auth:sanctum は各ルートで個別に指定する
        $middleware->api(append: [
            // \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            // \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
