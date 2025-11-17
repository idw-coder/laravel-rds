<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
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
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'ログイン情報が正しくありません。',
                ], 401);
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
        } catch (ValidationException $e) {
            // バリデーションエラーの場合
            return response()->json([
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // ログには常に詳細を記録
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // レスポンスは環境によって切り替え
            $response = [
                'message' => 'サーバーエラーが発生しました。',
            ];
            
            // 開発環境の場合は詳細を追加
            if (config('app.debug')) {
                $response['error'] = $e->getMessage();
                $response['file'] = $e->getFile();
                $response['line'] = $e->getLine();
            }
            
            return response()->json($response, 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'ログアウトしました。',
        ]);
    }
}
