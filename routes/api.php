<?php

use App\Http\Controllers\Api\FavoriteGroupCollectionController;
use App\Http\Controllers\Api\FavoriteWordGroupController;
use App\Http\Controllers\Api\GroupCollectionController;
use App\Http\Controllers\Api\GroupCollectionWordGroupController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SearchWordGroupController;
use App\Http\Controllers\Api\WordController;
use App\Http\Controllers\Api\WordGroupController;
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

        Route::get('search/word-groups', [SearchController::class, 'wordGroups'])->name('search.word-groups');
        Route::get('search/group-collections', [SearchController::class, 'groupCollections'])->name('search.group-collections');
        Route::get('word-groups/search', SearchWordGroupController::class)->name('word-groups.search');
        Route::apiResource('word-groups', WordGroupController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('word-groups.words', WordController::class)->shallow()->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('group-collections', GroupCollectionController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('group-collections/{group_collection}/word-groups', [GroupCollectionWordGroupController::class, 'store'])
            ->name('group-collections.word-groups.store');
        Route::delete('group-collections/{group_collection}/word-groups/{word_group}', [GroupCollectionWordGroupController::class, 'destroy'])
            ->name('group-collections.word-groups.destroy');
    });
});
