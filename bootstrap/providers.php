<?php

$providers = [
    App\Providers\AppServiceProvider::class,
];

// Telescopeはローカル環境でのみ有効化（クラスが存在する場合のみ）
if (app()->environment('local') && class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
