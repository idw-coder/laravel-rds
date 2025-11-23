<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens; // SanctumのAPIトークン機能を有効化
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Role;

class User extends Authenticatable
{
    /**
     * トレイトの有効化
     * HasApiTokens: APIトークンの生成・管理機能を提供（Sanctum）
     * HasFactory: モデルファクトリー機能を提供
     * Notifiable: 通知機能を提供（メール通知など）
     * 
     * @use HasFactory<\Database\Factories\UserFactory>
     */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token', // 注意: このプロジェクトでは未使用（API認証にSanctumを使用しているため、WebセッションのRemember Me機能は使用していない）
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * このユーザーが作成した投稿を取得
     * 
     * UserモデルとPostモデルの1対多のリレーションシップを定義
     * 1人のユーザーは複数の投稿を持つことができる
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * このユーザーが持つロール（多対多）
     *
     * belongsToMany:
     * - 第1引数: 関連先モデル（Role）
     * - 第2引数: 中間テーブル名（role_user）
     * 
     * これにより $user->roles() でロール一覧を取得でき、
     * attach(), sync(), detach() などで紐付け操作が可能になる。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
}
