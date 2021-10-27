<?php

use Illuminate\Support\Facades\Route;
use Maicol07\OIDCClient\Controllers\OIDCController;

Route::prefix(config('oidc.provider_name'))->middleware('web')->group(function () {
    Route::get('login', [OIDCController::class, 'login'])
        ->name('oidc.login');
    Route::get('logout', [OIDCController::class, 'logout'])
        ->name('oidc.logout');
    Route::get('callback', [OIDCController::class, 'callback'])
        ->name('oidc.callback');
});
