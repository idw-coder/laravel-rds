<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * roles テーブル:
     * - name: システム内部で使うロール識別子（例: free, paid, admin）
     * - label: 管理画面やUIで表示するための名前（例: 無料ユーザー / 有料ユーザー）
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();                                // 主キー
            $table->string('name')->unique();            // ロール識別子（例: 'free', 'paid', 'admin'）
            $table->string('label')->nullable();         // 表示名（任意、null 許可）
            $table->timestamps();                        // created_at / updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
