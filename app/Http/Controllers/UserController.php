<?php

namespace App\Http\Controllers;

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
        return response()->json($request->user());
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
        ]);

        // 名前とメールアドレスを更新
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // パスワードが入力されている場合のみ更新
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'プロフィールを更新しました',
            'user' => $user,
        ]);
    }
}