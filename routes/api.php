<?php

use App\Http\Controllers\Api\WordController;
use App\Http\Controllers\Api\WordGroupController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => new UserResource($request->user()))->name('api.user');

    Route::middleware('verified')->group(function () {
        Route::apiResource('word-groups', WordGroupController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('word-groups.words', WordController::class)->shallow()->only(['index', 'store', 'update', 'destroy']);
    });
});
