<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 先にロールを作成
        $this->call(RoleSeeder::class);

        // freeロールを取得
        $freeRoleId = \App\Models\Role::where('name', 'free')->value('id');

        // テストユーザー作成
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // freeロール付与
        if ($freeRoleId) {
            $user->roles()->attach($freeRoleId);
        }

        // adminロールを取得
        $adminRoleId = \App\Models\Role::where('name', 'admin')->value('id');

        // 管理者ユーザー作成
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // adminロール付与
        if ($adminRoleId) {
            $adminUser->roles()->attach($adminRoleId);
        }
    }
}
