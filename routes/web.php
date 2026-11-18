<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * The front-end is a Vue single-page application: every page is served by the
 * same view and Vue Router takes over. The named routes below are referenced by
 * Laravel itself (guest redirects, password reset and email verification links),
 * since Fortify does not register them when its views are disabled.
 */
Route::view('/dashboard', 'app')->name('dashboard');
Route::view('/login', 'app')->name('login');
Route::view('/reset-password/{token}', 'app')->name('password.reset');
Route::view('/email/verify', 'app')->name('verification.notice');

Route::fallback(function (Request $request) {
    abort_if($request->is('api/*') || $request->expectsJson(), 404);

    return view('app');
});
