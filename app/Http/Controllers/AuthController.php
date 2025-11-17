<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // バリデーション
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        /**
         * attempt(credentials): 認証情報を指定して認証を試行
         * only('email', 'password'): メールアドレスとパスワードを指定
         * 
         * 認証に失敗した場合は401 Unauthorizedを返却
         */
        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'ログイン情報が正しくありません。',
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'エラーが発生しました。',
                'error' => $e->getMessage(),
            ], 500);
        }

        // 認証済みユーザー取得
        /** @var User $user */
        $user = Auth::user();

        /** Sanctum トークン発行
         * createToken(name): トークン名を指定してトークンを発行
         * plainTextToken: トークンを文字列で返却
         */
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'ログアウトしました。',
        ]);
    }
}
