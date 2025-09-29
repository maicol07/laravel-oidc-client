<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'login')->withoutMiddleware('auth:web')->name('login');
