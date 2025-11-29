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

        /*
         * delete()メソッドについて
         * そもそもはdelete()メソッドは物理削除であり、論理削除ではありませんでした
         * 
         * これはUserクラスはIlluminate\Foundation\Auth\User（Illuminate\Database\Eloquent\Modelを継承しており、
         * そこで元々提供されていたdelete()メソッドは物理削除でした。
         * ↓
         * SoftDeletesトレイトを使用することで、delete()メソッドの挙動が論理削除に変わります。
         * 具体的には、データを削除ではなくdeleted_atに日付をいれる動作になります。
         */
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