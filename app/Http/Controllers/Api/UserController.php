<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|min:8|confirmed',
            'avatar' => 'nullable|file|image|max:2048', // 変更: file型、2MB以下
            'delete_avatar' => 'nullable|boolean', // 追加: 削除フラグ
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
            $file = $request->file('avatar');
            
            // バイナリデータとして読み込み
            $binary = file_get_contents($file->getRealPath());
            
            // DBに保存
            $user->avatar = $binary;
            $user->avatar_mime = $file->getMimeType(); // MIME typeも保存
        }

        $user->save();

        return response()->json([
            'message' => 'プロフィールを更新しました',
            'user' => $user,
        ]);
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
        
        return response($user->avatar)
            ->header('Content-Type', $user->avatar_mime ?? 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=31536000'); // 1年キャッシュ
    }
}