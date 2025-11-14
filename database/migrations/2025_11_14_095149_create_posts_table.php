<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * マイグレーション実行時に呼ばれる関数
     * データベースにテーブルを作成する
     */
    public function up(): void
    {
        // Schema::create() - 新しいテーブルを作成
        // 第1引数: テーブル名
        // 第2引数: Blueprint クロージャ - テーブル構造を定義
        Schema::create('posts', function (Blueprint $table) {
            // Blueprint - テーブルのカラム定義を行うクラス
            $table->id(); // 自動増分のプライマリキー
            $table->string('title'); // タイトル（VARCHAR 255）
            $table->text('content'); // 本文（TEXT型）
            $table->string('status')->default('draft'); // 状態（draft/published）
            $table->timestamps(); // created_at, updated_at 自動生成
            $table->softDeletes(); // deleted_at（論理削除用）
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};