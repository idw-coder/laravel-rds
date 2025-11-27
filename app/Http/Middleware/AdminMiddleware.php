<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * adminロールを持つユーザーのみアクセスを許可
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // リクエストから認証済みユーザーを取得、未認証の場合はnullを返す
        $user = $request->user();

        // 未認証の場合
        if (!$user) {
            return response()->json(['message' => '認証が必要です'], 401);
        }

        // adminロールを持っているか確認
        if (!$user->roles->contains('name', 'admin')) {
            return response()->json(['message' => '管理者権限が必要です'], 403);
        }

        return $next($request);
    }
}