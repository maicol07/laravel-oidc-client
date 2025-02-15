<?php

use Illuminate\Support\Facades\Route;
use Maicol07\OIDCClient\Controllers\OIDCController;
use Maicol07\OIDCClient\Http\OIDCStateMiddleware;

Route::prefix('oidc')->middleware('web')->group(function (): void {
    Route::get('login', [OIDCController::class, 'login'])
        ->name('oidc.login');
    Route::get('logout', [OIDCController::class, 'logout'])
        ->name('oidc.logout');
    Route::match(['get', 'post'], config('oidc.callback_route_path', 'callback'), [OIDCController::class, 'callback'])
        ->middleware(OIDCStateMiddleware::class)
        ->name('oidc.callback');
});
