<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ローカル環境のみTelescopeを登録（class_existsチェック追加）
        if ($this->app->environment('local')) {
            // 文字列リテラルを使用することでIDEの型チェックエラーを回避（PHPDocで対応）
            // if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            //     $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            /** @var string $telescopeProviderClass */
            $telescopeProviderClass = 'Laravel\Telescope\TelescopeServiceProvider';
            if (class_exists($telescopeProviderClass)) {
                $this->app->register($telescopeProviderClass);
                $this->app->register(\App\Providers\TelescopeServiceProvider::class);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
