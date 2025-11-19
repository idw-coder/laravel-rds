<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * roles テーブルに初期ロールを追加する。
     * - free : 無料ユーザー
     * - paid : 有料ユーザー（Stripe決済後に付与）
     * - admin: 管理者
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'free',            // システム内部向け識別子
                'label' => '無料ユーザー',    // 管理画面等の表示名
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'paid',
                'label' => '有料ユーザー',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'label' => '管理者',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
