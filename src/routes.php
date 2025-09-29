<?php

use Illuminate\Support\Facades\Route;
use Maicol07\OIDCClient\Controllers\OIDCController;
use Maicol07\OIDCClient\Http\OIDCStateMiddleware;

Route::group([
    'prefix' => config('oidc.routes.prefix', 'oidc'),
    'middleware' => config('oidc.routes.middleware', ['web']),
    'as' => config('oidc.routes.name', 'oidc.'),
], static function (): void {
    Route::get(config('oidc.routes.login', 'login'), [OIDCController::class, 'login'])
        ->name('login');
    Route::get(config('oidc.routes.logout', 'logout'), [OIDCController::class, 'logout'])
        ->name('logout');
    Route::match(['get', 'post'], config('oidc.routes.callback', 'callback'), [OIDCController::class, 'callback'])
        ->middleware(OIDCStateMiddleware::class)
        ->name('callback');
});
