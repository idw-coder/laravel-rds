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
            'avatar' => 'nullable|string',
        ]);

        // 名前とメールアドレスを更新
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // パスワードが入力されている場合のみ更新
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // アバターの処理
        if (array_key_exists('avatar', $validated)) {
            if ($validated['avatar'] === null || $validated['avatar'] === '') {
                // 削除の場合
                $user->avatar = null;
            } elseif (!empty($validated['avatar'])) {
                // 更新の場合
                $avatar = $validated['avatar'];
                
                // data:image/png;base64, などのプレフィックスを削除
                if (preg_match('/^data:image\/(\w+);base64,/', $avatar)) {
                    $avatar = substr($avatar, strpos($avatar, ',') + 1);
                }
                
                $user->avatar = $avatar;
            }
        }

        $user->save();

        return response()->json([
            'message' => 'プロフィールを更新しました',
            'user' => $user,
        ]);
    }
}