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

        // ロールID取得
        $freeRoleId  = \App\Models\Role::where('name', 'free')->value('id');
        $paidRoleId  = \App\Models\Role::where('name', 'paid')->value('id');
        $adminRoleId = \App\Models\Role::where('name', 'admin')->value('id');

        // freeユーザー
        $freeUser = User::factory()->create([
            'name'     => 'Free User',
            'email'    => 'free@example.com',
            'password' => Hash::make('password'),
        ]);
        $freeUser->roles()->attach($freeRoleId);

        // paidユーザー
        $paidUser = User::factory()->create([
            'name'     => 'Paid User',
            'email'    => 'paid@example.com',
            'password' => Hash::make('password'),
        ]);
        $paidUser->roles()->attach($paidRoleId);

        // adminユーザー
        $adminUser = User::factory()->create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $adminUser->roles()->attach($adminRoleId);

        // 投稿シーダー
        $this->call(PostSeeder::class);
    }
}
