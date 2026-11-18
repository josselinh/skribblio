<?php

use App\Http\Controllers\Api\GroupCollectionController;
use App\Http\Controllers\Api\GroupCollectionWordGroupController;
use App\Http\Controllers\Api\SearchWordGroupController;
use App\Http\Controllers\Api\WordController;
use App\Http\Controllers\Api\WordGroupController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => new UserResource($request->user()))->name('api.user');

    Route::middleware('verified')->group(function () {
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
