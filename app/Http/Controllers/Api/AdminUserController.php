<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * ユーザー一覧を取得（論理削除済みも含む）
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = User::withTrashed()
            ->with('roles')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * ユーザーを論理削除
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 自分自身は削除不可
        if ($user->id === $request->user()?->id) {
            return response()->json(['message' => '自分自身は削除できません'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'ユーザーを削除しました']);
    }

    /**
     * 論理削除したユーザーを復元
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['message' => 'ユーザーを復元しました']);
    }
}