<?php

$providers = [
    App\Providers\AppServiceProvider::class,
];

// Telescopeはローカル環境でのみ有効化
if (app()->environment('local')) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
