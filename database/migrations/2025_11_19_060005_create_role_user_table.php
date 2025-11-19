<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * role_user テーブル（中間テーブル）
     * - ユーザーとロールを紐付けるための多対多の中間テーブル
     * - 外部キー制約を付与
     * - user_id + role_id の組をユニークにして重複を防止
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();

            // user_id（users.id と紐づく）
            $table->unsignedBigInteger('user_id');

            // role_id（roles.id と紐づく）
            $table->unsignedBigInteger('role_id');

            // どのロールがいつ付与されたかを追いたい場合に timestamps が有用
            $table->timestamps();

            // 外部キー制約：ユーザーが削除されたら関連ロールも削除
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // 外部キー制約：ロールが削除されたら紐付けも削除
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            // user_id と role_id の組み合わせでユニーク（重複登録を防止）
            $table->unique(['user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};