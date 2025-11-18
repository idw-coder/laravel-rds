<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Google認証画面へのリダイレクトURLを返す
     */
    public function redirectToGoogle()
    {
        return response()->json([
            // stateless() は 実際に存在します Intelephense が正しく認識できていないだけ
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl()
        ]);
    }

    /**
     * Googleからのコールバックを処理
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Googleからユーザー情報を取得
            // stateless() は 実際に存在します Intelephense が正しく認識できていないだけ
            $googleUser = Socialite::driver('google')->stateless()->user();

            // google_id または email でユーザーを検索
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if ($user) {
                // 既存ユーザーの場合、google_id を更新（未設定の場合）
                if (!$user->google_id) {
                    $user->google_id = $googleUser->id;
                    $user->save();
                }
            } else {
                // 新規ユーザーを作成
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null, // Googleログインユーザーはパスワード不要
                ]);
            }

            // トークンを生成
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google認証に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}