<?php

use App\Http\Controllers\Api\AccessRequestController;
use App\Http\Controllers\Api\DuplicateGroupCollectionController;
use App\Http\Controllers\Api\DuplicateWordGroupController;
use App\Http\Controllers\Api\FavoriteGroupCollectionController;
use App\Http\Controllers\Api\FavoriteWordGroupController;
use App\Http\Controllers\Api\GroupCollectionController;
use App\Http\Controllers\Api\GroupCollectionSharingController;
use App\Http\Controllers\Api\GroupCollectionWordGroupController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SearchWordGroupController;
use App\Http\Controllers\Api\SharedWithMeController;
use App\Http\Controllers\Api\ShareInvitationController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\WordController;
use App\Http\Controllers\Api\WordExportController;
use App\Http\Controllers\Api\WordGroupController;
use App\Http\Controllers\Api\WordGroupSharingController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => new UserResource($request->user()))->name('api.user');

    Route::middleware('verified')->group(function () {
        Route::get('favorites/word-groups', [FavoriteWordGroupController::class, 'index'])->name('favorites.word-groups.index');
        Route::put('word-groups/{word_group}/favorite', [FavoriteWordGroupController::class, 'update'])->name('word-groups.favorite.update');
        Route::delete('word-groups/{word_group}/favorite', [FavoriteWordGroupController::class, 'destroy'])->name('word-groups.favorite.destroy');
        Route::get('favorites/group-collections', [FavoriteGroupCollectionController::class, 'index'])->name('favorites.group-collections.index');
        Route::put('group-collections/{group_collection}/favorite', [FavoriteGroupCollectionController::class, 'update'])->name('group-collections.favorite.update');
        Route::delete('group-collections/{group_collection}/favorite', [FavoriteGroupCollectionController::class, 'destroy'])->name('group-collections.favorite.destroy');

        // Sharing, for the creator of a private group or collection.
        Route::controller(WordGroupSharingController::class)->prefix('word-groups/{word_group}')->name('word-groups.')->group(function () {
            Route::get('share-link', 'shareLink')->name('share-link.show');
            Route::post('share-link', 'regenerateShareLink')->name('share-link.regenerate');
            Route::get('access', 'access')->name('access.show');
            Route::delete('members/{user}', 'revoke')->name('members.destroy');
        });
        Route::controller(GroupCollectionSharingController::class)->prefix('group-collections/{group_collection}')->name('group-collections.')->group(function () {
            Route::get('share-link', 'shareLink')->name('share-link.show');
            Route::post('share-link', 'regenerateShareLink')->name('share-link.regenerate');
            Route::get('access', 'access')->name('access.show');
            Route::delete('members/{user}', 'revoke')->name('members.destroy');
            Route::post('hidden-word-groups/access-requests', 'requestHiddenWordGroups')->name('hidden-word-groups.access-requests.store');
        });

        // Share links, as opened by the people they were sent to.
        Route::controller(ShareInvitationController::class)->prefix('share')->name('share.')->group(function () {
            Route::get('word-groups/{word_group:share_token}', 'showWordGroup')->name('word-groups.show');
            Route::post('word-groups/{word_group:share_token}/access-request', 'requestWordGroup')->name('word-groups.access-request');
            Route::get('group-collections/{group_collection:share_token}', 'showGroupCollection')->name('group-collections.show');
            Route::post('group-collections/{group_collection:share_token}/access-request', 'requestGroupCollection')->name('group-collections.access-request');
        });

        // Access requests received by creators, and items shared with the current user.
        Route::controller(AccessRequestController::class)->prefix('access-requests')->name('access-requests.')->group(function () {
            Route::get('received', 'received')->name('received');
            Route::post('word-groups/{word_group_access_request}/accept', 'acceptWordGroup')->name('word-groups.accept');
            Route::post('word-groups/{word_group_access_request}/refuse', 'refuseWordGroup')->name('word-groups.refuse');
            Route::post('group-collections/{group_collection_access_request}/accept', 'acceptGroupCollection')->name('group-collections.accept');
            Route::post('group-collections/{group_collection_access_request}/refuse', 'refuseGroupCollection')->name('group-collections.refuse');
        });
        Route::get('shared/word-groups', [SharedWithMeController::class, 'wordGroups'])->name('shared.word-groups');
        Route::get('shared/group-collections', [SharedWithMeController::class, 'groupCollections'])->name('shared.group-collections');

        // Another user's page: their groups and collections visible to the current user.
        Route::controller(UserProfileController::class)->prefix('users/{user}')->name('users.')->group(function () {
            Route::get('/', 'show')->name('show');
            Route::get('word-groups', 'wordGroups')->name('word-groups');
            Route::get('group-collections', 'groupCollections')->name('group-collections');
        });

        // Skribbl.io export: every word, unpaginated.
        Route::get('word-groups/{word_group}/export', [WordExportController::class, 'wordGroup'])->name('word-groups.export');
        Route::post('word-groups/{word_group}/duplicate', DuplicateWordGroupController::class)->name('word-groups.duplicate');
        Route::post('group-collections/{group_collection}/duplicate', DuplicateGroupCollectionController::class)->name('group-collections.duplicate');
        Route::get('group-collections/{group_collection}/export', [WordExportController::class, 'groupCollection'])->name('group-collections.export');

        Route::get('search/word-groups', [SearchController::class, 'wordGroups'])->name('search.word-groups');
        Route::get('search/group-collections', [SearchController::class, 'groupCollections'])->name('search.group-collections');
        Route::get('word-groups/search', SearchWordGroupController::class)->name('word-groups.search');
        Route::apiResource('word-groups', WordGroupController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::apiResource('word-groups.words', WordController::class)->shallow()->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('group-collections', GroupCollectionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::post('group-collections/{group_collection}/word-groups', [GroupCollectionWordGroupController::class, 'store'])
            ->name('group-collections.word-groups.store');
        Route::delete('group-collections/{group_collection}/word-groups/{word_group}', [GroupCollectionWordGroupController::class, 'destroy'])
            ->name('group-collections.word-groups.destroy');
    });
});
