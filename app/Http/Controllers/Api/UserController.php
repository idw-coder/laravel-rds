<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * 現在のユーザー情報を取得
     */
    public function show(Request $request)
    {
        return response()->json($request->user()->load('roles'));
    }

    /**
     * ユーザー情報を更新
     */
    public function update(Request $request)
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'password' => 'nullable|min:8|confirmed',
                'avatar' => 'nullable|file|image|max:5120', // file型、5MB以下
                // 'delete_avatar' => 'nullable|boolean', // 削除フラグ
                'delete_avatar' => 'nullable|string',
            ]);

            // 名前とメールアドレスを更新
            $user->name = $validated['name'];
            $user->email = $validated['email'];

            // パスワードが入力されている場合のみ更新
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            // アバター削除の処理
            if ($request->input('delete_avatar') === 'true' || $request->input('delete_avatar') === true) {
                $user->avatar = null;
                $user->avatar_mime = null;
            }
            // アバターアップロードの処理
            elseif ($request->hasFile('avatar')) {

                // $fileはオブジェクト（主なプロパティはファイルパス、ファイル名、ファイルサイズ、ファイルタイプ、ファイルパスなど）
                $file = $request->file('avatar');
                
                /**
                 * file_get_contents: ファイルの内容を文字列として読み込む
                 * @param string $filename ファイルパス
                 * @return string ファイルの内容（バイナリデータ）
                 */
                $binary = file_get_contents($file->getRealPath());
                // PHPではバイナリデータは文字列データstringで扱う、JavaScriptではBufferで扱う
                
                // DBに保存
                $user->avatar = $binary;
                $user->avatar_mime = $file->getMimeType(); // MIME typeも保存（HTTPレスポンスのContent-Typeヘッダーで使用）
            }

            $user->save();

            return response()->json([
                'message' => 'プロフィールを更新しました',
                'user' => $user,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\QueryException $e) {
            // DB関連エラー（max_allowed_packet等）
            Log::error('Profile update DB error: ' . $e->getMessage());
            
            return response()->json([
                'message' => '画像の保存に失敗しました。ファイルサイズを小さくしてお試しください。',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'プロフィールの更新に失敗しました',
            ], 500);
        }
    }

    /**
     * アバター画像を取得
     */
    public function getAvatar(Request $request, $userId)
    {
        $user = \App\Models\User::findOrFail($userId);
        
        if (!$user->avatar) {
            return response()->json(['error' => 'Avatar not found'], 404);
        }
        
        /**
         *   $user->avatar はBLOBカラムのバイナリデータ（文字列）
         *   クライアント側では、このエンドポイントを画像のURLとして直接使用できる
         */
        return response($user->avatar)
            ->header('Content-Type', $user->avatar_mime ?? 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=31536000'); // 1年キャッシュ
    }
}