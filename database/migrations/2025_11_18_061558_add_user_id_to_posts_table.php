<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * マイグレーションを実行する
     * 
     * postsテーブルにuser_idカラムを追加し、usersテーブルへの外部キー制約を設定します。
     * 既存データがある場合でも安全にマイグレーションできるよう、複数ステップで実行します。
     * これにより、各投稿がどのユーザーに紐づいているかを管理できるようになります。
     */
    public function up(): void
    {
        // user_idカラムが既に存在する場合は削除
        // マイグレーションを再実行する場合や、既存のカラムをクリーンアップする場合に必要
        if (Schema::hasColumn('posts', 'user_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        Schema::table('posts', function (Blueprint $table) {
            // ステップ1: まずNULL許可でuser_idカラムを追加
            // ->nullable(): NULL値を許可（既存データがある場合に必要）
            // ->after('id'): idカラムの後に配置
            $table->foreignId('user_id')->nullable()->after('id');
        });

        // ステップ2: 既存データに最初のユーザーを割り当て
        // usersテーブルから最初のユーザーIDを取得（存在する場合）
        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        
        // ユーザーが存在する場合のみ既存投稿を更新
        // user_idがNULLの既存レコードに、最初のユーザーを割り当てる
        if ($firstUserId) {
            DB::table('posts')->whereNull('user_id')->update(['user_id' => $firstUserId]);
        }

        Schema::table('posts', function (Blueprint $table) {
            // ステップ3: NULL不可に変更して外部キー制約を追加
            // ->nullable(false)->change(): NULL不可に変更
            // ->foreign(): usersテーブルのidカラムへの外部キー制約を設定
            // ->onDelete('cascade'): ユーザーが削除された場合、そのユーザーの投稿も自動的に削除される
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * マイグレーションをロールバック（取り消し）する
     * 
     * up()メソッドで行った変更を元に戻します。
     * 外部キー制約を削除してから、user_idカラムを削除します。
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // 外部キー制約を削除（先に削除しないとカラムが削除できない）
            $table->dropForeign(['user_id']);
            // user_idカラムを削除
            $table->dropColumn('user_id');
        });
    }
};