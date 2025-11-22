<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request) // Request は引数 $request の型（型ヒント）$request が Request クラスのインスタンス
    {
        try {
            // 配列の各キーを値のルールでバリデーション
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // ユーザーを検索
            $existingUser = User::where('email', $request->email)->first();

            // Googleログイン専用ユーザーの場合
            if ($existingUser && is_null($existingUser->password)) { // Googleログインでユーザーを作成する際に password を null に設定しているため
                return response()->json([
                    'message' => 'このアカウントは Google アカウントでログインしてください。',
                ], 400);
            }

            // メール・パスワード認証
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'ログイン情報が正しくありません。',
                ], 401);
            }

            // 認証済みユーザー
            /** @var User $user */
            $user = Auth::user();

            /** Sanctum トークン発行
             * createToken(name): トークン名を指定してトークンを発行
             * plainTextToken: トークンを文字列で返却
             */
            $authToken = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'authToken' => $authToken,
                
                // ロールを含めて返す（重要）
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ],
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

    public function register(Request $request)
    {
        try {
            // バリデーション
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ]);

            // ユーザー作成
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);

            // free ロールを付与
            $freeRole = Role::where('name', 'free')->first();
            if ($freeRole) {
                $user->roles()->attach($freeRole->id);
            }

            /** Sanctum トークン発行
             * createToken(name): トークン名を指定してトークンを発行
             * plainTextToken: トークンを文字列で返却
             */
            $authToken = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'authToken' => $authToken,
                // ロールを含めて返す（重要）
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ],
            ], 201);
        } catch (ValidationException $e) {
            // バリデーションエラーの場合
            return response()->json([
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // ログには常に詳細を記録
            Log::error('Register error', [
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
