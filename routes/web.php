<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\RobotsTxtController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
 * The front-end is a Vue single-page application: every page is served by the same view and Vue Router takes
 * over. PageController gives each page its meta tags for search engines (only the public pages are indexed)
 * and answers 404 for a path the SPA does not know. The named routes are referenced by Laravel itself (guest
 * redirects, password reset and email verification links), since Fortify does not register them when its
 * views are disabled. Keep this list in sync with resources/js/router/index.ts.
 */
Route::get('/robots.txt', RobotsTxtController::class);
Route::get('/sitemap.xml', SitemapController::class);

Route::controller(PageController::class)->group(function () {
    Route::get('/', 'home');
    Route::get('/explore', 'explore');
    Route::get('/dashboard', 'application')->name('dashboard');
    Route::get('/login', 'application')->name('login');
    Route::get('/reset-password/{token}', 'application')->name('password.reset');
    Route::get('/email/verify', 'application')->name('verification.notice');
    Route::get('/register', 'application');
    Route::get('/forgot-password', 'application');
    Route::get('/groups/new', 'application');
    Route::get('/groups/{id}/edit', 'application');
    Route::get('/collections/new', 'application');
    Route::get('/collections/{id}/edit', 'application');
    Route::get('/share/{type}/{token}', 'application')->where('type', 'groups|collections');
    Route::get('/users/{id}', 'application');
    Route::get('/profile', 'application');
    Route::get('/security', 'application');
    Route::get('/preferences', 'application');

    // After `/groups/new` and `/collections/new`, which they would match.
    Route::get('/groups/{wordGroupId}', 'wordGroup');
    Route::get('/collections/{groupCollectionId}', 'groupCollection');

    Route::fallback('fallback');
});
